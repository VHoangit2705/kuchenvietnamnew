<?php

namespace App\Mail;

use App\Models\KyThuat\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $notification;

    /**
     * Create a new message instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->notification->title)
                    ->view('emails.order-status-change')
                    ->with([
                        'notification' => $this->notification,
                        'agency' => $this->notification->agency,
                        'requestAgency' => $this->notification->requestAgency,
                    ]);
    }
}

