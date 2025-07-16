<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Order Cancelled Notification')
            ->view('admin.templates.templates-for-mails.order_cancelled')
            ->with([
                'name' => $this->order->user->first_name . ' ' . $this->order->user->last_name,
                'order_id' => $this->order->order_id,
                'date' => $this->order->cancel_order_date->format('Y-m-d H:i:s'),
                'reason' => $this->order->cancel_reason,
                'charges' => $this->order->cancellation_charges ?? 'None'
            ]);
    }
}
