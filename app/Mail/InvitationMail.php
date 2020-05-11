<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $UrlToShare;
    public function __construct($UrlToShare)
    {
        $this->UrlToShare = $UrlToShare;
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
                    ->subject('Profile Sharing Manager: Invitation mail')
                    ->view('emails.invitation')
                    ->with([
                  'UrlToShare' => $this->UrlToShare,
                ]);
    }
}
