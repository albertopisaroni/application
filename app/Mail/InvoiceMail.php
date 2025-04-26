<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice, public string $pdfPath) {}

    public function build()
    {
        return $this->subject("Fattura {$this->invoice->invoice_number}")
            ->view('emails.invoice')
            ->attachFromStorageDisk('s3', $this->pdfPath, 'Fattura.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}