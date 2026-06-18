<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $activationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activeer je Boels CORE account',
            to: [['address' => $this->user->email, 'name' => $this->user->name]],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-activation',
            with: [
                'user' => $this->user,
                'url' => $this->activationUrl,
                'brand' => config('boels.brand'),
            ],
        );
    }
}
