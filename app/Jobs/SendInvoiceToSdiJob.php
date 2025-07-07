<?php
namespace App\Jobs;

use App\Models\Invoice;
use App\Services\InvoiceXmlGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendInvoiceToSdiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $invoiceId;

    public function __construct(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    public function handle(InvoiceXmlGenerator $xmlGen) 
    {
        $invoice = Invoice::findOrFail($this->invoiceId);
        $xml     = $xmlGen->generate($invoice);

        Log::info("📤 Inviando fattura {$invoice->id} a SDI", [
            'xml' => $xml,
        ]);

        $resp = Http::withHeaders([
            'Content-Type'  => 'application/xml',
            'Authorization' => 'Bearer '.config('services.openapi.sdi.token'),
        ])->withBody($xml, 'application/xml')
          ->post(config('services.openapi.sdi.url').'/invoices_signature_legal_storage');

        if ($resp->successful() && $uuid = $resp->json('data.uuid')) {
            $invoice->update([
                'sdi_uuid'    => $uuid,
                'sdi_status'  => 'sent',
                'sdi_sent_at' => now(),
            ]);
        } else {
            $invoice->increment('sdi_attempt');
            $invoice->update([
                'sdi_status'            => 'error',
                'sdi_error'             => $resp->status(),
                'sdi_error_description' => $resp->body(),
            ]);
            // rilancia per eventuali retry automatici
            throw new \Exception("Errore SDI: ".$resp->body());
        }
    }
}