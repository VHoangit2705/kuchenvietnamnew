<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $fromDate;
    public $toDate;
    public $reportType;
    public $pdfPath;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($pdfPath, $fromDate, $toDate, $reportType = 'tuần')
    {
        $this->pdfPath = $pdfPath;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->reportType = $reportType;
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
        $mail = $this->from('nghia520414@gmail.com', config('mail.from.name', 'Công ty'))
            ->subject('Báo cáo thống kê bảo hành ' . $this->reportType . ' - ' . $this->fromDate . ' đến ' . $this->toDate)
            ->view('emails.report')
            ->with([
                'fromDate' => $this->fromDate,
                'toDate' => $this->toDate,
                'reportType' => $this->reportType,
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
}

