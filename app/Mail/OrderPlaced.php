<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    private $name = 'Sir/Madam';
    private $companyName = '';
    private $date = '';
    private $order_id = '';
    private $content = '...';
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $companyName, $date, $order_id, $content)
    {
        $this->name = $name;
        $this->companyName = $companyName;
        $this->date = $date;
        $this->order_id = $order_id;
        $this->content = $content;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Order Placed',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'admin.templates.templates-for-mails.order-placed-template',
            with: [
                'content' => $this->content,
                'name' => $this->name,
                'companyName' => $this->companyName,
                'date' => $this->date,
                'order_id' => $this->order_id,
            ],
        );
    }


    public function build()
    {
        $contentWithReplacements = str_replace(
            ['--userName--', '--companyName--', '--date--', '--order_id--'],
            [$this->name, $this->companyName, $this->date, $this->order_id],
            $this->content
        );

        return $this->view('admin.templates.templates-for-mails.order-placed-template', ['content' => $contentWithReplacements]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
