<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $resetLink; // Change from OTP to Reset Link

    /**
     * Create a new message instance.
     */
    public function __construct($name, $resetLink)
    {
        $this->name = $name;
        $this->resetLink = $resetLink;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Password Reset Request - Mowing and Plowing')
                    ->view('admin.templates.templates-for-mails.password-reset')
                    ->with([
                        'name' => $this->name,
                        'resetLink' => $this->resetLink, // Pass the reset link to the view
                    ]);
    }
}
