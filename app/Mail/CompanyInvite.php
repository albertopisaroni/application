<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Company;
use App\Models\User;

class CompanyInvite extends Mailable
{
    use Queueable, SerializesModels;

    public Company $company;
    public User $user;

    public function __construct(Company $company, User $user)
    {
        $this->company = $company;
        $this->user = $user;
    }

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'Invito a unirti a ' . $this->company->name,
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            markdown: 'emails.company.invite',
            with: [
                'company' => $this->company,
                'user' => $this->user,
                'link' => url('/invito/' . $this->user->invitation_token),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}