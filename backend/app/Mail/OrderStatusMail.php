<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $orderNumber,
        public readonly string $status,
        public readonly ?string $reason = null
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->status) {
            'placed' => "Order {$this->orderNumber} confirmed",
            'paid' => "Payment received for order {$this->orderNumber}",
            'fulfilled' => "Order {$this->orderNumber} has been delivered",
            'cancelled' => "Order {$this->orderNumber} cancelled",
            default => "Order {$this->orderNumber} update",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status',
        );
    }
}
