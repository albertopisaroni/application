<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

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
            ])->with([
                'invoice' => $this->invoice,
                'url'     => Storage::disk('s3')->temporaryUrl($this->pdfPath, now()->addMinutes(5)),
            ]);
    }
}