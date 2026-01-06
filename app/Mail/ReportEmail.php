<?php

namespace App\Mail;

use App\Models\KyThuat\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class ReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $fromDate;
    public $toDate;
    public $reportType;
    public $pdfPath;
    public $zoneKey;
    public $zoneLabel;
    public $weekNumber;
    public $monthNumber;
    public $periodTitle;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        $pdfPath,
        $fromDate,
        $toDate,
        $reportType = 'tuần',
        $zoneKey = 'all',
        $zoneLabel = null,
        $weekNumber = null,
        $monthNumber = null,
        $periodTitle = null
    )
    {
        $this->pdfPath = $pdfPath;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->reportType = $reportType;
        $this->zoneKey = $zoneKey ?: 'all';
        $this->zoneLabel = $zoneLabel ?: ($this->zoneKey === 'all' ? 'Tất cả chi nhánh' : strtoupper($this->zoneKey));
        $this->weekNumber = $weekNumber;
        $this->monthNumber = $monthNumber ?? $this->extractMonthNumber();
        $this->periodTitle = $periodTitle;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // CẤU HÌNH EMAIL GỬI ĐI
        // from(): Email và tên người gửi
        //   - Email đầu tiên: Địa chỉ email gửi đi (phải khớp với MAIL_FROM_ADDRESS trong .env)
        //   - Tham số thứ 2: Tên hiển thị (lấy từ config('mail.from.name') hoặc mặc định 'Công ty')
        // subject(): Tiêu đề email
        // view(): Template email (resources/views/emails/report.blade.php)
        $reportLabel = $this->buildReportLabel();
        $subject = 'Báo cáo thống kê bảo hành ' . $reportLabel . ' - ' . $this->fromDate . ' đến ' . $this->toDate;

        if (!empty($this->zoneLabel)) {
            $subject .= ' (' . $this->zoneLabel . ')';
        }

        $mail = $this->from('nghia520414@gmail.com', config('mail.from.name', 'Công ty'))
            ->subject($subject)
            ->view('emails.report')
            ->with([
                'fromDate' => $this->fromDate,
                'toDate' => $this->toDate,
                'reportType' => $this->reportType,
                'zoneLabel' => $this->zoneLabel,
                'zoneKey' => $this->zoneKey,
                'periodTitle' => $this->resolvePeriodTitle(),
            ]);

        // ĐÍNH KÈM FILE PDF VÀO EMAIL
        if (file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as' => basename($this->pdfPath),
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    /**
     * Get all report recipients filtered by the provided positions.
     */
    public static function reportRecipients(array $positions)
    {
        $query = User::query()
            ->select('id', 'email', 'full_name', 'position', 'zone')
            ->whereNotNull('email')
            ->where('email', '!=', '');

        $positions = array_values(array_filter($positions));

        if (!empty($positions)) {
            $normalized = array_map(function ($position) {
                return mb_strtolower(trim($position));
            }, $positions);

            $query->where(function ($builder) use ($normalized) {
                foreach ($normalized as $index => $position) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $builder->{$method}('LOWER(position) = ?', [$position]);
                }
            });
        }

        return $query->get();
    }

    protected function buildReportLabel(): string
    {
        if ($this->reportType === 'tuần' && $this->weekNumber) {
            $monthLabel = $this->monthNumber ? 'tháng ' . $this->monthNumber : null;
            return 'tuần thứ ' . $this->weekNumber . ($monthLabel ? ' (' . $monthLabel . ')' : '');
        }

        if ($this->reportType === 'tháng' && $this->monthNumber) {
            return 'tháng ' . $this->monthNumber;
        }

        return $this->reportType;
    }

    protected function resolvePeriodTitle(): string
    {
        if (!empty($this->periodTitle)) {
            return $this->periodTitle;
        }

        if ($this->reportType === 'tuần' && $this->weekNumber) {
            $monthSegment = $this->monthNumber ? ' trong tháng ' . $this->monthNumber : '';
            return 'Tuần thứ ' . $this->weekNumber . $monthSegment;
        }

        if ($this->reportType === 'tháng' && $this->monthNumber) {
            return 'Tháng ' . $this->monthNumber;
        }

        return ucfirst($this->reportType);
    }

    protected function extractMonthNumber(): ?int
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $this->toDate)->month;
        } catch (\Exception $e) {
            return null;
        }
    }
}

