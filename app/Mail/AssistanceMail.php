<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AssistanceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $mail;
    public function __construct($mail)
    {
        //
        $this->mail = $mail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // return $this->view('view.name');
        return $this->from('info@psm.com', 'Profile Sharing Manager')
                    ->subject('Profile Sharing Manager: Assistance mail')
                    ->view('emails.assistance')
                    ->with([
                    'mail' => $this->mail,
                ]);
    }
}
