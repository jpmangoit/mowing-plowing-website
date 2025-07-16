<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobCompletedProviderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $providerName;
    public $serviceType;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($providerName, $serviceType)
    {
        $this->providerName = $providerName;
        $this->serviceType = $serviceType;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    
    public function build()
    {
        return $this->subject("Job Complete – You're All Set!")
                    ->view('admin.templates.templates-for-mails.job-completed-provider');
    }
}
