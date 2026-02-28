<?php

namespace App\Mail;

use App\Models\Kho\Product;
use App\Models\products_new\ProductContentReview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContentReviewNotification extends Mailable
{
    use Queueable;

    public $product;
    public $review;

    /**
     * Create a new message instance.
     */
    public function __construct(Product $product, ProductContentReview $review)
    {
        $this->product = $product;
        $this->review = $review;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = $this->review->status === 'approved' ? 'ĐÃ DUYỆT' : 'BỊ TỪ CHỐI';
        return new Envelope(
            subject: "[$status] Thông báo kết quả duyệt nội dung: " . $this->product->product_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.content_review_result',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
