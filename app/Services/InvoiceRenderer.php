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
        // Carica il template corretto in base al tipo di documento
        $this->templateHtml = $this->loadTemplate();
    }

    private function loadTemplate(): string
    {
        $numbering = $this->invoice->numbering;
        $template = null;

        // Determina quale template caricare in base al tipo di documento
        if ($this->invoice->document_type === 'TD04') {
            // Note di credito - usa template_credit_id
            if ($numbering->template_credit_id) {
                $template = \App\Models\InvoiceTemplate::find($numbering->template_credit_id);
            }
        } else {
            // Fatture e altri documenti - usa template_invoice_id
            if ($numbering->template_invoice_id) {
                $template = \App\Models\InvoiceTemplate::find($numbering->template_invoice_id);
            }
        }

        // Se non trova il template specifico, usa il primo disponibile
        if (!$template) {
            $template = \App\Models\InvoiceTemplate::first();
        }

        return $template?->blade ?? '';
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
            : view('invoices.blocks.notforfettario')->render();

        // 2) Rows articoli
        $invoiceRows = view('invoices.blocks.items', compact('items', 'company'))->render();

        // 3) Metodo pagamento
        $pm = $this->paymentMethod;
        
        $paymentMethodBlock = $pm
            ? view('invoices.blocks.payment_method', compact('pm'))->render()
            : '';

            // Gestione $paymentScheduleBlock in base al tipo di documento
            if ($this->invoice->document_type === 'TD04') {
                // Note di credito: mostra fattura di riferimento se presente
                // Carica esplicitamente la relazione originalInvoice
                $this->invoice->load('originalInvoice');
                
                if ($this->invoice->originalInvoice) {
                    $originalInvoice = $this->invoice->originalInvoice;
                    $originalDate = Carbon::parse($originalInvoice->issue_date)->format('d/m/Y');
                    $paymentScheduleBlock = <<<HTML
                    <div class="px-14 py-3 text-sm">
                      <p class="font-bold">Fattura di riferimento:</p>
                      <p>Fattura {$originalInvoice->invoice_number} del {$originalDate}</p>
                    </div>
                    HTML;
                } else {
                    // Nota di credito senza fattura originale
                    $paymentScheduleBlock = '';
                }
            } else {
                // Fatture normali: mostra scadenze di pagamento
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

        if($company->regime_fiscale !== 'RF19'){
            $totaleText = 'Totale (IVA inclusa):';
            $tdIva = '<td class="border-b-2 pb-3 pl-2 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >IVA (%)</td><td class="border-b-2 pb-3 pl-2 pr-4 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >Totale (IVA escl.)</td>';
            $trIva = '
                <tr>
                    <td class="border-b p-3 w-full"></td>
                    <td class="border-b p-3">
                    <div class="whitespace-nowrap text-neutral-700">Totale (IVA escl.):</div>
                    </td>
                    <td class="border-b p-3 text-right">
                    <div class="whitespace-nowrap text-neutral-700">€'.number_format($this->invoice->subtotal,2,',','.').'</div>
                    </td>
                </tr>

                <tr>
                    <td class="border-b p-3 w-full"></td>
                    <td class="border-b p-3">
                    <div class="whitespace-nowrap text-neutral-700">Importo totale IVA:</div>
                    </td>
                    <td class="border-b p-3 text-right">
                    <div class="whitespace-nowrap text-neutral-700">€'.number_format($this->invoice->vat,2,',','.').'</div>
                    </td>
                </tr>';
        }
        else{
            $totaleText = 'Totale:';
            $tdIva = '<td class="border-b-2 pb-3 pl-2 pr-4 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >Totale</td>';
            $trIva = '';
        }

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
            '{{ $tdIva }}'               => $tdIva,
            '{{ $trIva }}'               => $trIva,
            '{{ $totaleText }}'          => $totaleText,
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