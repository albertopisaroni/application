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
        return $this->from('fatture@newo.io', $this->company->name . ' (via Newo.io)')
                    ->subject("Ecco la nuova fattura n. {$this->invoice->invoice_number}")
                    ->view('emails.invoice')
                    ->with([
                        'invoice' => $this->invoice,
                        'url'     => $this->invoice->pdf_url,
                    ]);
    }
}