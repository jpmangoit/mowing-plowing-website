<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobCompletedCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $serviceType;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($customerName, $serviceType)
    {
        $this->customerName = $customerName;
        $this->serviceType = $serviceType;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Your Job is Complete! Let Us Know How We Did!')
                    ->view('admin.templates.templates-for-mails.job-completed-customer');
    }
}
