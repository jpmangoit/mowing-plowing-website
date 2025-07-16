<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $password;
    public $url;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $email, $password, $url)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->url = $url;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Welcome to Mowing and Plowing')
                    ->view('admin.templates.templates-for-mails.registration')
                    ->with([
                        'name' => $this->name,
                        'email' => $this->email,
                        'password' => $this->password,
                        'url' => $this->url,
                    ]);
    }
}
