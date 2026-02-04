<?php

namespace App\Models\KyThuat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class WarrantyReportSnapshot extends Model
{
    protected $table = 'warranty_report_snapshots';

    protected $fillable = [
        'report_type',
        'from_date',
        'to_date',
        'snapshot_date',
        'branch',
        'warranty_data',
        'work_process_data',
        'summary_data',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'from_date' => 'datetime',
        'to_date' => 'datetime',
        'snapshot_date' => 'datetime',
        'summary_data' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Lấy warranty_data đã decode từ JSON
     */
    public function getWarrantyDataDecodedAttribute()
    {
        return json_decode($this->warranty_data, true) ?? [];
    }

    /**
     * Lấy work_process_data đã decode từ JSON
     */
    public function getWorkProcessDataDecodedAttribute()
    {
        return json_decode($this->work_process_data, true) ?? [];
    }

    /**
     * Tìm snapshot theo khoảng thời gian và loại báo cáo
     */
    public static function findSnapshot(string $reportType, Carbon $fromDate, Carbon $toDate, string $branch = 'all'): ?self
    {
        return self::where('report_type', $reportType)
            ->where('from_date', $fromDate->format('Y-m-d H:i:s'))
            ->where('to_date', $toDate->format('Y-m-d H:i:s'))
            ->where('branch', $branch)
            ->first();
    }

    /**
     * Tìm snapshot gần nhất cho tuần hiện tại (dựa trên ngày gửi email - thứ 3)
     * Khi gửi email vào thứ 3, tìm snapshot đã lưu vào thứ 2 tuần trước
     */
    public static function findLatestWeeklySnapshot(string $branch = 'all'): ?self
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        
        // Tìm thứ 2 tuần trước (tuần của khoảng thời gian báo cáo)
        // Nếu hôm nay là thứ 3, thì thứ 2 tuần trước là 8 ngày trước
        $lastMonday = $now->copy()->previous(Carbon::MONDAY)->subWeek()->startOfDay();
        
        // Thứ 2 tuần này (kết thúc khoảng thời gian)
        $thisMonday = $lastMonday->copy()->addWeek()->endOfDay();

        return self::where('report_type', 'weekly')
            ->where('branch', $branch)
            ->where('from_date', '>=', $lastMonday->format('Y-m-d H:i:s'))
            ->where('to_date', '<=', $thisMonday->format('Y-m-d H:i:s'))
            ->orderBy('snapshot_date', 'desc')
            ->first();
    }

    /**
     * Tìm snapshot chưa gửi email
     */
    public static function findUnsentSnapshots(string $reportType): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('report_type', $reportType)
            ->where('is_sent', false)
            ->orderBy('snapshot_date', 'desc')
            ->get();
    }

    /**
     * Đánh dấu đã gửi email
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'is_sent' => true,
            'sent_at' => Carbon::now('Asia/Ho_Chi_Minh'),
        ]);
    }

    /**
     * Tạo hoặc cập nhật snapshot
     */
    public static function createOrUpdateSnapshot(array $data): self
    {
        return self::updateOrCreate(
            [
                'report_type' => $data['report_type'],
                'from_date' => $data['from_date'],
                'to_date' => $data['to_date'],
                'branch' => $data['branch'] ?? 'all',
            ],
            [
                'snapshot_date' => $data['snapshot_date'],
                'warranty_data' => $data['warranty_data'],
                'work_process_data' => $data['work_process_data'],
                'summary_data' => $data['summary_data'] ?? null,
                'is_sent' => false,
                'sent_at' => null,
            ]
        );
    }
}