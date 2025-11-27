<?php

namespace App\Services;

use App\Models\KyThuat\WarrantyRequest;
use App\Models\KyThuat\User;
use App\Models\KyThuat\WarrantyAnomalyAlert;
use App\Models\KyThuat\WarrantyAnomalyBlock;
use Carbon\Carbon;

class WarrantyAnomalyDetector
{
    const ALERT_THRESHOLD = 2.5; // Hệ số cảnh báo
    const BLOCK_DURATION_HOURS = 1; // Chặn trong 1 giờ

    /**
     * Kiểm tra và chặn nếu nhân viên vượt ngưỡng
     * 
     * @param string $staffName Tên nhân viên
     * @param string $branch Chi nhánh
     * @param string|null $date Ngày kiểm tra (mặc định: hôm nay)
     * @return array ['blocked' => bool, 'message' => string, 'block_info' => array|null]
     */
    public function checkAndBlock($staffName, $branch, $date = null)
    {
        if (!$date) {
            $date = Carbon::today();
        }

        // Kiểm tra xem đã bị chặn chưa
        $existingBlock = WarrantyAnomalyBlock::getActiveBlock($staffName, $branch, $date);
        if ($existingBlock) {
            $now = Carbon::now();
            $blockedUntil = Carbon::parse($existingBlock->blocked_until);
            
            // Tính thời gian còn lại
            $remainingSeconds = $now->diffInSeconds($blockedUntil, false);
            
            if ($remainingSeconds <= 0) {
                // Thời gian chặn đã hết
                return [
                    'blocked' => false,
                    'message' => null,
                    'block_info' => null
                ];
            }
            
            $remainingMinutes = floor($remainingSeconds / 60);
            $remainingSecs = $remainingSeconds % 60;
            
            // Format thời gian còn lại
            $timeRemaining = '';
            if ($remainingMinutes > 0) {
                $timeRemaining = $remainingMinutes . ' phút';
                if ($remainingSecs > 0) {
                    $timeRemaining .= ' ' . $remainingSecs . ' giây';
                }
            } else {
                $timeRemaining = $remainingSecs . ' giây';
            }
            
            // Lấy thông tin số ca khi bị chặn
            $countWhenBlocked = $existingBlock->count_when_blocked ?? 0;
            $threshold = $existingBlock->threshold ?? 0;
            
            $message = $this->formatBlockMessage($countWhenBlocked, $threshold, $timeRemaining, true);
            
            return [
                'blocked' => true,
                'message' => $message,
                'block_info' => $existingBlock
            ];
        }

        // Tính toán số liệu
        $stats = $this->calculateStats($branch, $date);

        // Nếu không đủ dữ liệu để đánh giá
        if ($stats['staff_count_in_branch'] < 2 || $stats['total_count'] < 5) {
            return [
                'blocked' => false,
                'message' => null,
                'block_info' => null
            ];
        }

        // Lấy số ca của nhân viên này
        $staffCount = WarrantyRequest::where('staff_received', $staffName)
            ->where('branch', $branch)
            ->whereDate('received_date', $date)
            ->count();

        // Tính ngưỡng: Dựa trên số nhân viên trong kho
        // Ngưỡng = (Tổng số ca / Số nhân viên) * 1.5
        // Đảm bảo 1 nhân viên không nhận quá 1.5 lần trung bình
        // Nhưng tối đa không quá 50% tổng số ca
        $averagePerStaff = $stats['staff_count_in_branch'] > 0 
            ? $stats['total_count'] / $stats['staff_count_in_branch'] 
            : 0;
        $thresholdByAverage = ceil($averagePerStaff * 2.5);
        $thresholdByPercentage = ceil($stats['total_count'] * 0.5);
        
        // Lấy giá trị nhỏ hơn để đảm bảo công bằng hơn (fix ko bao giờ lấy min)
        $threshold = $thresholdByPercentage;

        // Kiểm tra xem có vượt ngưỡng không
        if ($staffCount >= $threshold) {
            // Kiểm tra xem đã có block nào được admin gỡ (is_active = false) trong ngày đó chưa
            // Nếu đã được admin gỡ block rồi thì không chặn lại trong ngày đó
            $wasUnblocked = WarrantyAnomalyBlock::where('staff_name', $staffName)
                ->where('branch', $branch)
                ->where('date', $date)
                ->where('is_active', false)
                ->exists();
            
            // Nếu đã được admin gỡ block rồi thì không chặn lại trong ngày đó
            if ($wasUnblocked) {
                return [
                    'blocked' => false,
                    'message' => null,
                    'block_info' => null
                ];
            }

            // Tạo cảnh báo
            $this->createAlert($staffName, $branch, $date, $staffCount, $stats, $threshold);

            // Chặn trong 1 giờ
            $blockedUntil = Carbon::now()->addHours(self::BLOCK_DURATION_HOURS);
            WarrantyAnomalyBlock::create([
                'staff_name' => $staffName,
                'branch' => $branch,
                'date' => $date,
                'blocked_until' => $blockedUntil,
                'count_when_blocked' => $staffCount,
                'threshold' => $threshold,
                'is_active' => true,
            ]);

            $message = $this->formatBlockMessage($staffCount, $threshold, '1 giờ', false);
            
            return [
                'blocked' => true,
                'message' => $message,
                'block_info' => [
                    'count' => $staffCount,
                    'threshold' => $threshold,
                    'blocked_until' => $blockedUntil->format('H:i:s')
                ]
            ];
        }

        return [
            'blocked' => false,
            'message' => null,
            'block_info' => null
        ];
    }

    /**
     * Tính toán thống kê cho một kho trong ngày
     * 
     * @param string $branch Chi nhánh
     * @param string|null $date Ngày
     * @return array
     */
    public function calculateStats($branch, $date = null)
    {
        if (!$date) {
            $date = Carbon::today();
        }

        // Tổng số ca của kho trong ngày
        $totalCount = WarrantyRequest::where('branch', $branch)
            ->whereDate('received_date', $date)
            ->count();

        // Số nhân viên trong kho (dựa vào users.zone)
        // Lấy zone từ branch (ví dụ: "KUCHEN VINH" -> "VINH")
        $zone = $this->extractZoneFromBranch($branch);
        $staffCountInBranch = User::where('zone', 'like', "%{$zone}%")
            ->count();

        // Tính trung bình
        $averageCount = $staffCountInBranch > 0 ? $totalCount / $staffCountInBranch : 0;

        return [
            'total_count' => $totalCount,
            'staff_count_in_branch' => $staffCountInBranch,
            'average_count' => round($averageCount, 2),
        ];
    }

    /**
     * Tạo cảnh báo
     */
    private function createAlert($staffName, $branch, $date, $staffCount, $stats, $threshold)
    {
        // Kiểm tra xem đã có cảnh báo chưa
        $existingAlert = WarrantyAnomalyAlert::where('staff_name', $staffName)
            ->where('branch', $branch)
            ->where('date', $date)
            ->where('is_resolved', false)
            ->first();

        if (!$existingAlert) {
            // Xác định mức độ cảnh báo dựa trên tỷ lệ so với ngưỡng (50% tổng số ca)
            // Ngưỡng = 50% tổng số ca
            // Nếu nhân viên tiếp nhận >= 50% và < 60% → mức thấp
            // Nếu nhân viên tiếp nhận >= 60% và < 70% → mức trung bình
            // Nếu nhân viên tiếp nhận >= 70% → mức cao
            $percentage = ($stats['total_count'] > 0) ? ($staffCount / $stats['total_count']) * 100 : 0;
            $alertLevel = 'medium';
            if ($percentage >= 70) {
                $alertLevel = 'high';
            } elseif ($percentage < 60) {
                $alertLevel = 'low';
            }

            WarrantyAnomalyAlert::create([
                'branch' => $branch,
                'staff_name' => $staffName,
                'date' => $date,
                'staff_count' => $staffCount,
                'total_count' => $stats['total_count'],
                'staff_count_in_branch' => $stats['staff_count_in_branch'],
                'average_count' => $stats['average_count'],
                'threshold' => $threshold,
                'alert_level' => $alertLevel,
                'is_resolved' => false,
            ]);
        }
    }

    /**
     * Lấy danh sách cảnh báo (chỉ admin)
     */
    public function getAlerts($date = null, $branch = null, $resolved = false)
    {
        $query = WarrantyAnomalyAlert::query();

        if ($date) {
            $query->where('date', $date);
        } else {
            // Mặc định lấy cảnh báo trong 7 ngày gần nhất
            $query->where('date', '>=', Carbon::today()->subDays(7));
        }

        if ($branch) {
            $query->where('branch', $branch);
        }

        if ($resolved !== null) {
            $query->where('is_resolved', $resolved);
        }

        return $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Format thông báo chặn
     */
    private function formatBlockMessage($staffCount, $threshold, $timeRemaining, $isExistingBlock = false)
    {
        $thresholdFormatted = number_format($threshold, 0);
        $staffCountFormatted = number_format($staffCount, 0);
        
        if ($isExistingBlock) {
            return "⚠️ HỆ THỐNG PHÁT HIỆN HOẠT ĐỘNG BẤT THƯỜNG. Tài khoản của bạn đang tạm thời bị hạn chế tiếp nhận ca bảo hành Thời gian còn lại: {$timeRemaining}.\n\nVui lòng liên hệ bộ phận quản lý để được hỗ trợ.";
        } else {
            return "⚠️ HỆ THỐNG PHÁT HIỆN HOẠT ĐỘNG BẤT THƯỜNG. Tài khoản của bạn đã bị hạn chế tiếp nhận ca bảo hành trong {$timeRemaining}.\n\nVui lòng liên hệ bộ phận quản lý để được hỗ trợ.";
        }
    }

    /**
     * Trích xuất zone từ branch
     * Ví dụ: "KUCHEN VINH" -> "VINH", "HUROM HÀ NỘI" -> "HÀ NỘI"
     */
    private function extractZoneFromBranch($branch)
    {
        $parts = explode(' ', $branch);
        if (count($parts) > 1) {
            return implode(' ', array_slice($parts, 1));
        }
        return $branch;
    }
}

