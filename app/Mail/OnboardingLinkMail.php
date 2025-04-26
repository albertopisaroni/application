<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnboardingLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $link;

    public function __construct(string $link)
    {
        $this->link = $link;
    }

    public function build()
    {
        return $this->subject('Completa la tua registrazione su Newo')
                    ->view('emails.onboarding-link');
    }
}