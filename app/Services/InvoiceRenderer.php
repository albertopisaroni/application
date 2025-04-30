<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentMethod;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceRenderer
{
    protected Invoice $invoice;
    protected $items;
    protected ?PaymentMethod $paymentMethod;
    protected array $paymentSchedule;
    protected string $templateHtml;

    public function __construct(Invoice $invoice, $items = null, $paymentSchedule = null, $splitPayment = null, $dueDate = null)
    {
        $this->invoice      = $invoice;
        $this->items        = $items;
        $this->paymentMethod   = $this->invoice->payment_method_id
            ? PaymentMethod::find($this->invoice->payment_method_id)
            : null;
        $this->paymentSchedule = $paymentSchedule;
        $this->splitPayment = $splitPayment;
        $this->dueDate      = $dueDate;
        $this->templateHtml = $invoice->numbering->template->blade; // relazione Invoice→Numbering→Template
    }

    public function renderHtml(): string
    {
        $company  = $this->invoice->company;
        $client   = $this->invoice->client;
        $items    = $this->items;
        $schedules = $this->paymentSchedule;
        $num      = $this->invoice->numbering;

        // 1) Forfettario
        $forfettarioBlock = $company->forfettario
            ? view('invoices.blocks.forfettario')->render()
            : '';

        // 2) Rows articoli
        $invoiceRows = view('invoices.blocks.items', compact('items'))->render();

        // 3) Metodo pagamento
        $pm = $this->paymentMethod;
        
        $paymentMethodBlock = $pm
            ? view('invoices.blocks.payment_method', compact('pm'))->render()
            : '';

            if (! $this->splitPayment) {
                // single payment: valore = totale fattura
                $dt = isset($this->dueDate) 
                    ? Carbon::parse($this->dueDate)->format('d/m/Y') 
                    : Carbon::parse($this->invoice->issue_date)->addDays(30)->format('d/m/Y');
                $amt = number_format($this->invoice->total, 2, ',', '.');
                $paymentScheduleBlock = <<<HTML
                <div class="px-14 py-3 text-sm">
                  <p class="font-bold">Scadenze pagamento:</p>
                  <p>€{$amt} – {$dt}</p>
                </div>
                HTML;
            } else {
                // split payments: cicla su tutte le rate
                $lines = '';
                foreach ($this->paymentSchedule as $p) {
                    $rawAmt = ($p['type'] ?? '') === 'percent'
                        ? round($this->invoice->total * ($p['value']/100), 2)
                        : floatval($p['value']);
                    $amt = number_format($rawAmt, 2, ',', '.');
                    $dt  = Carbon::parse($p['date'])->format('d/m/Y');
                    $lines .= "<p>€{$amt} – {$dt}</p>";
                }
                $paymentScheduleBlock = <<<HTML
                <div class="px-14 py-3 text-sm">
                  <p class="font-bold">Scadenze pagamento:</p>
                  {$lines}
                </div>
                HTML;
            }

        $intestazione = $this->invoice->header_notes 
            ? view('invoices.blocks.intestazione', ['header_notes' => $this->invoice->header_notes])->render() 
            : '';

        $note = $this->invoice->footer_notes 
        ? view('invoices.blocks.note', ['footer_notes' => $this->invoice->footer_notes])->render() 
        : '';


        $sconto = $this->invoice->global_discount
            ? view('invoices.blocks.sconto', ['sconto' => number_format($this->invoice->global_discount,2,',','.')])->render() 
            : '';

        // 5) Monta array variabili
        $vars = [
            '{{ $invoiceDate }}'         => Carbon::parse($this->invoice->issue_date)->format('d/m/Y'),
            '{{ $invoiceNumber }}'       => $this->invoice->invoice_number,
            '{{ $subtotal }}'            => number_format($this->invoice->subtotal,2,',','.'),
            '{{ $vatTotal }}'            => number_format($this->invoice->vat,2,',','.'),
            '{{ $price }}'               => number_format($this->invoice->total,2,',','.'),
            '{{ $globalDiscountBlock }}' => $sconto,
            '{{ $headerNotesBlock }}'    => $intestazione,
            '{{ $footerNotesBlock }}'    => $note,
            '{{ $forfettarioBlock }}'    => $forfettarioBlock,
            '{{ $companyLogo }}'         => $num->logo_base64 ?? '',
            '{{ $invoiceRows }}'         => $invoiceRows,
            '{{ $paymentMethodBlock }}'  => $paymentMethodBlock,
            '{{ $paymentScheduleBlock }}'=> $paymentScheduleBlock,
            // dati cliente
            '{{ $clientName }}'     => $client->name ?? null,
            '{{ $clientPIVA }}'     => $client->piva ?? null,
            '{{ $clientAddress }}'  => $client->address ?? null,
            '{{ $clientCAP }}'      => $client->cap ?? null,
            '{{ $clientCity }}'     => $client->city ?? null,
            '{{ $clientProvince }}' => $client->province ?? null,
            // dati azienda
            '{{ $companyName }}'     => $company->legal_name ?? $company->name,
            '{{ $companyVat }}'      => $company->piva,
            '{{ $companyReaBlock }}' => $company->tax_code
                ? "<p>R.E.A: {$company->tax_code}</p>"
                : '',
            '{{ $companyEmailBlock }}'=> $company->email
                ? "<p>Email: {$company->email}</p>"
                : '',
            '{{ $companyPecBlock }}'  => $company->pec_email
                ? "<p>PEC: {$company->pec_email}</p>"
                : '',
        ];

        return str_replace(
            array_keys($vars),
            array_values($vars),
            $this->templateHtml
        );
    }

    public function renderPdf(): string
    {
        $html = $this->renderHtml();
        return Pdf::loadHTML($html)->setPaper('a4')->output();
    }
}