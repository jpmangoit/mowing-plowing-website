<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduleDateUpdate extends Mailable
{
    use Queueable, SerializesModels;

    private $name = 'Sir/Madam';
    private $date = '';
    private $newDate = '';
    private $content = '...';
    private $email = '';
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $date, $newDate, $content,$email)
    {
        $this->name = $name;
        $this->date = $date;
        $this->newDate = $newDate;
        $this->email = $email;
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
            subject: 'Job scheduled has updated',
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
            view: 'admin.templates.templates-for-mails.daterescheduled-template',
            with: [
                'content' => $this->content,
                'name' => $this->name,
                'email' => $this->email,
                'date' => $this->date,
                'newDate' => $this->newDate
            ],
        );
    }


    public function build()
    {
        $contentWithReplacements = str_replace(
            ['--userName--','--date--','--newDate-- ', '--email--'],
            [$this->name,$this->date,$this->newDate,$this->email],
            $this->content
        );

        return $this->view('admin.templates.templates-for-mails.daterescheduled-template', ['content' => $contentWithReplacements]);
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
