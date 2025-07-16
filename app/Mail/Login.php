<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Login extends Mailable
{
    use Queueable, SerializesModels;
    private $name = 'Sir/Madam';
    private $url = '';
    private $companyName = '';
    private $email = '';
    private $password = '';
    private $role = '';
    private $content = '...';
    /**
     * Create a new message instance.
     *
     * @return void
     */
   public function __construct($name, $url, $companyName, $email, $password, $content)
    {
        $this->name = $name;
        $this->url = $url;
        $this->companyName = $companyName;
        $this->email = $email;
        $this->password = $password;
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
            subject: 'App Login',
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
            view: 'admin.templates.templates-for-mails.login-template',
            with: [
                'content' => $this->content,
                'name' => $this->name,
                'url' => $this->url,
                'companyName' => $this->companyName,
                'email' => $this->email,
                'password' => $this->password,
            ],
        );
    }
    
       public function build()
    {
        $contentWithReplacements = str_replace(
            ['--userName--', '--companyName--', '--url--', '--email--', '--password--'],
            [$this->name, $this->companyName, $this->url, $this->email, $this->password],
            $this->content
        );

        return $this->view('admin.templates.templates-for-mails.login-template', ['content' => $contentWithReplacements]);
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
