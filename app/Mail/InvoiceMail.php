<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function build()
    {    
        return $this->subject("Fattura {$this->invoice->invoice_number}")
            ->view('emails.invoice')
            ->with([
                'invoice' => $this->invoice,
                'url'     => $this->invoice->pdf_url,
            ]);
    }
}