<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Mail\InvoiceMail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $invoiceId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("🚀 Avvio job per invio email fattura {$this->invoiceId}");
            
            $invoice = Invoice::with(['client.contacts', 'company'])->findOrFail($this->invoiceId);
            
            $recipients = $invoice->client->contacts()
                ->where('receives_invoice_copy', 1)
                ->pluck('email')
                ->toArray();

            if (empty($recipients)) {
                Log::info("📭 Nessun contatto configurato per il cliente {$invoice->client->name}");
                return;
            }

            foreach ($recipients as $email) {
                Mail::to($email)->send(new InvoiceMail($invoice, $invoice->company));
                Log::info("📤 Fattura {$invoice->invoice_number} inviata a: $email");
            }

            Log::info("✅ Job completato con successo per fattura {$this->invoiceId}");
            
        } catch (\Exception $e) {
            Log::error("❌ Errore nel job per fattura {$this->invoiceId}", ['exception' => $e]);
            throw $e;
        }
    }
} 