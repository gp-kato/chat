<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function build() {
        return $this->view('emails.group_invitation')
        ->with([
            'group' => $this->group,
            'url' => $this->url,
        ]);
    }

    public $group;
    public $url;

    public function __construct($group, $url) {
        $this->group = $group;
        $this->url = $url;
    }

    public function envelope() {
        return new Envelope(
            subject: 'Group Invitation',
        );
    }

    public function content() {
        return new Content(
            view: 'emails.group_invitation',
        );
    }

    public function attachments() {
        return [];
    }
}
