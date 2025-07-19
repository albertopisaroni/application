<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice, 
        public Company $company
    ) {}

    public function build()
    {    
        // Map country code to locale for translations
        $locale = match($this->invoice->client->country) {
            'IT' => 'it',
            'ES' => 'es',
            'UK' => 'en',
            'FR' => 'fr',
            default => 'en'
        };
        
        return $this->from('fatture@newo.io', $this->company->name . ' (' . __('emails.via_newo', [], $locale) . ')')
                    ->subject(__('emails.invoice_subject', ['number' => $this->invoice->invoice_number], $locale))
                    ->view('emails.invoice')
                    ->with([
                        'invoice' => $this->invoice,
                        'url'     => $this->invoice->pdf_url,
                    ]);
    }
}