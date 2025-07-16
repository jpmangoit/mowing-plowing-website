<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeclineCard extends Mailable
{
    use Queueable, SerializesModels;
    private $name = 'Sir/Madam';
    private $companyName = '';
    private $email = '';
    private $content = '...';
    public $subject = '';
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $companyName, $email, $content,$subject)
    {
        $this->name = $name;
        $this->companyName = $companyName;
        $this->email = $email;
        $this->content = $content;
        $this->subject = $subject;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subject,
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
            view: 'admin.templates.templates-for-mails.order-template',
            with: [
                'content' => $this->content,
                'name' => $this->name,
                'companyName' => $this->companyName,
                'email' => $this->email
            ],
        );
    }

    public function build()
    {
        $contentWithReplacements = str_replace(
            ['--userName--', '--companyName--', '--email--', ],
            [$this->name, $this->companyName, $this->email],
            $this->content
        );

        return $this->view('admin.templates.templates-for-mails.order-template', ['content' => $contentWithReplacements]);
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
