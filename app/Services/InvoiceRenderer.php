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

        if ($template && $template->id) {
            $viewPath = "template.invoices.{$template->id}.template";
            if (view()->exists($viewPath)) {
                return view($viewPath, ['template' => $template, 'company' => $this->invoice->company, 'client' => $this->invoice->client])->render();
            }
        }

        return '';
    }

    public function renderHtml(): string
    {
        $company  = $this->invoice->company;
        $client   = $this->invoice->client;
        $items    = $this->items;
        $schedules = $this->paymentSchedule;
        $num      = $this->invoice->numbering;
        
        
        if ($this->invoice->document_type === 'TD04') {
            // Note di credito - usa template_credit_id
            if ($num->template_credit_id) {
                $template = \App\Models\InvoiceTemplate::find($num->template_credit_id);
            }
        } else {
            // Fatture e altri documenti - usa template_invoice_id
            if ($num->template_invoice_id) {
                $template = \App\Models\InvoiceTemplate::find($num->template_invoice_id);
            }
        }

        // Se non trova il template specifico, usa il primo disponibile
        if (!$template) {
            $template = \App\Models\InvoiceTemplate::first();
        }

        // 1) Forfettario
        $forfettarioBlock = $company->forfettario
            ? view('template.invoices.'.$template->id.'.blocks.forfettario', compact('client', 'company'))->render()
            : view('template.invoices.'.$template->id.'.blocks.notforfettario', compact('client', 'company'))->render();

        // 2) Rows articoli
        $invoiceRows = view('template.invoices.'.$template->id.'.blocks.items', compact('items', 'company'))->render();

        // 3) Metodo pagamento
        $pm = $this->paymentMethod;
    

        $paymentMethodBlock = $pm
            ? view('template.invoices.'.$template->id.'.blocks.payment_method', compact('pm', 'client', 'company'))->render()
            : '';

            // Gestione $paymentScheduleBlock in base al tipo di documento
            if ($this->invoice->document_type === 'TD04') {
                // Note di credito: mostra fattura di riferimento se presente
                // Carica esplicitamente la relazione originalInvoice
                $this->invoice->load('originalInvoice');
                
                if ($this->invoice->originalInvoice) {
                    $originalInvoice = $this->invoice->originalInvoice;
                    $originalDate = Carbon::parse($originalInvoice->issue_date)->format('d/m/Y');
                    $locale = match(strtoupper($client?->country ?? $company->legal_country ?? 'IT')) {
                        'IT' => 'it',
                        'ES' => 'es',
                        'UK' => 'en',
                        'GB' => 'en',
                        'EN' => 'en',
                        'FR' => 'fr',
                        default => strtolower($company->legal_country ?? 'it'),
                    };
                    $referenceLabel = \Lang::get('invoices.Fattura di riferimento', [], $locale);
                    $invoiceLabel = \Lang::get('invoices.Fattura', [], $locale);
                    $delLabel = \Lang::get('invoices.del', [], $locale);
                    $paymentScheduleBlock = <<<HTML
                    <div class="px-14 py-3 text-sm">
                      <p class="font-bold">{$referenceLabel}:</p>
                      <p>{$invoiceLabel} {$originalInvoice->invoice_number} {$delLabel} {$originalDate}</p>
                    </div>
                    HTML;
                } else {
                    // Nota di credito senza fattura originale
                    $paymentScheduleBlock = '';
                }
            } else {
                // Fatture normali: mostra scadenze di pagamento
                if (! $this->splitPayment) {
                    $dt = isset($this->dueDate) 
                        ? Carbon::parse($this->dueDate)->format('d/m/Y') 
                        : Carbon::parse($this->invoice->issue_date)->addDays(30)->format('d/m/Y');
                    $amt = number_format($this->invoice->total, 2, ',', '.');
                    $locale = match(strtoupper($client?->country ?? $company->legal_country ?? 'IT')) {
                        'IT' => 'it',
                        'ES' => 'es',
                        'UK' => 'en',
                        'GB' => 'en',
                        'EN' => 'en',
                        'FR' => 'fr',
                        default => strtolower($company->legal_country ?? 'it'),
                    };
                    $scadenzeLabel = \Lang::get('invoices.Scadenze pagamento', [], $locale);
                    $paymentScheduleBlock = <<<HTML
                    <div class="px-14 py-3 text-sm">
                      <p class="font-bold">{$scadenzeLabel}:</p>
                      <p>€{$amt} – {$dt}</p>
                    </div>
                    HTML;
                } else {
                    $lines = '';
                    foreach ($this->paymentSchedule as $p) {
                        $rawAmt = ($p['type'] ?? '') === 'percent'
                            ? round($this->invoice->total * ($p['value']/100), 2)
                            : floatval($p['value']);
                        $amt = number_format($rawAmt, 2, ',', '.');
                        $dt  = Carbon::parse($p['date'])->format('d/m/Y');
                        $lines .= "<p>€{$amt} – {$dt}</p>";
                    }
                    $locale = match(strtoupper($client?->country ?? $company->legal_country ?? 'IT')) {
                        'IT' => 'it',
                        'ES' => 'es',
                        'UK' => 'en',
                        'GB' => 'en',
                        'EN' => 'en',
                        'FR' => 'fr',
                        default => strtolower($company->legal_country ?? 'it'),
                    };
                    $scadenzeLabel = \Lang::get('invoices.Scadenze pagamento', [], $locale);
                    $paymentScheduleBlock = <<<HTML
                    <div class="px-14 py-3 text-sm">
                      <p class="font-bold">{$scadenzeLabel}:</p>
                      {$lines}
                    </div>
                    HTML;
                }
            }

        $intestazione = $this->invoice->header_notes 
            ? view('template.invoices.'.$template->id.'.blocks.intestazione', ['header_notes' => $this->invoice->header_notes, 'client' => $client, 'company' => $company])->render() 
            : '';

        $note = $this->invoice->footer_notes 
        ? view('template.invoices.'.$template->id.'.blocks.note', ['footer_notes' => $this->invoice->footer_notes, 'client' => $client, 'company' => $company])->render() 
        : '';


        $sconto = $this->invoice->global_discount
            ? view('template.invoices.'.$template->id.'.blocks.sconto', ['sconto' => number_format($this->invoice->global_discount,2,',','.'), 'client' => $client, 'company' => $company])->render() 
            : '';

        $theadBlock = view('template.invoices.'.$template->id.'.blocks.thead', compact('client', 'company'))->render();

        $localeClient = match(strtoupper($client?->country ?? $company->legal_country ?? 'IT')) {
            'IT' => 'it',
            'ES' => 'es',
            'UK' => 'en',
            'GB' => 'en',
            'EN' => 'en',
            'FR' => 'fr',
            default => strtolower($company->legal_country ?? 'it'),
        };

        $localeCompany = match(strtoupper($company->legal_country ?? 'IT')) {
            'IT' => 'it',
            'ES' => 'es',
            'UK' => 'en',
            'GB' => 'en',
            'EN' => 'en',
            'FR' => 'fr',
            default => strtolower($company->legal_country ?? 'it'),
        };
        
        if($company->regime_fiscale !== 'RF19'){
            $totaleText = \Lang::get('invoices.Totale (IVA inclusa)', [], $localeClient) . ':';
            $ivaLabel = \Lang::get('invoices.IVA (%)', [], $localeClient);
            $totaleEsclusaLabel = \Lang::get('invoices.Totale (IVA escl.)', [], $localeClient);
            $importoIvaLabel = \Lang::get('invoices.Importo totale IVA', [], $localeClient);
            
            $tdIva = '<td class="border-b-2 pb-3 pl-2 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >'.$ivaLabel.'</td><td class="border-b-2 pb-3 pl-2 pr-4 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >'.$totaleEsclusaLabel.'</td>';
            $trIva = '
                <tr>
                    <td class="border-b p-3 w-full"></td>
                    <td class="border-b p-3">
                    <div class="whitespace-nowrap text-neutral-700">'.$totaleEsclusaLabel.':</div>
                    </td>
                    <td class="border-b p-3 text-right">
                    <div class="whitespace-nowrap text-neutral-700">€'.number_format($this->invoice->subtotal,2,',','.').'</div>
                    </td>
                </tr>

                <tr>
                    <td class="border-b p-3 w-full"></td>
                    <td class="border-b p-3">
                    <div class="whitespace-nowrap text-neutral-700">'.$importoIvaLabel.':</div>
                    </td>
                    <td class="border-b p-3 text-right">
                    <div class="whitespace-nowrap text-neutral-700">€'.number_format($this->invoice->vat,2,',','.').'</div>
                    </td>
                </tr>';
        }
        else{
            $totaleText = \Lang::get('invoices.Totale', [], $localeClient) . ':';
            $totaleLabel = \Lang::get('invoices.Totale', [], $localeClient);
            $tdIva = '<td class="border-b-2 pb-3 pl-2 pr-4 text-right font-bold " style="border-color: #0d172b; color: #0d172b;" >'.$totaleLabel.'</td>';
            $trIva = '';
        }

        // 5) Monta array variabili
        $vars = [
            '[[ $invoiceDate ]]'         => Carbon::parse($this->invoice->issue_date)->format('d/m/Y'),
            '[[ $invoiceNumber ]]'       => $this->invoice->invoice_number,
            '[[ $subtotal ]]'            => number_format($this->invoice->subtotal,2,',','.'),
            '[[ $vatTotal ]]'            => number_format($this->invoice->vat,2,',','.'),
            '[[ $price ]]'               => number_format($this->invoice->total,2,',','.'),
            '[[ $globalDiscountBlock ]]' => $sconto,
            '[[ $headerNotesBlock ]]'    => $intestazione,
            '[[ $footerNotesBlock ]]'    => $note,
            '[[ $forfettarioBlock ]]'    => $forfettarioBlock,
            '[[ $companyLogo ]]'         => $num->logo_base64 ?? '',
            '[[ $invoiceRows ]]'         => $invoiceRows,
            '[[ $paymentMethodBlock ]]'  => $paymentMethodBlock,
            '[[ $paymentScheduleBlock ]]'=> $paymentScheduleBlock,
            '[[ $theadBlock ]]'          => $theadBlock,
            '[[ $tdIva ]]'               => $tdIva,
            '[[ $trIva ]]'               => $trIva,
            '[[ $totaleText ]]'          => $totaleText,
            
            // dati cliente
            '[[ $clientName ]]'     => $client?->name ?? null,
            '[[ $clientPIVA ]]'     => $client?->piva ?? null,
            '[[ $clientAddress ]]'  => $client?->address ?? null,
            '[[ $clientCAP ]]'      => $client?->cap ?? null,
            '[[ $clientCity ]]'     => $client?->city ?? null,
            '[[ $clientProvince ]]' => $client?->province ? '(' . $client?->province . ')' : '',
            '[[ $clientCountry ]]'  => $client?->country ?? null,

            // dati azienda
            '[[ $companyName ]]'     => $company->name ?? $company->legal_name,

            '[[ $companyVat ]]'      => $this->formatPiva($company->piva, $client?->country, $company->legal_country),
            
            '[[ $companyReaBlock ]]' => $company->tax_code
                ? "<p>" . \Lang::get('invoices.R.E.A', [], $localeCompany) . ": {$company->rea_numero}</p>"
                : '',

            '[[ $companyEmailBlock ]]' => $company->email
                ? "<p>" . \Lang::get('invoices.Email', [], $localeCompany) . ": {$company->email}</p>"
                : '',

            '[[ $companyPecBlock ]]'  => $company->pec_email
                ? "<p>" . \Lang::get('invoices.PEC', [], $localeCompany) . ": {$company->pec_email}</p>"
                : '',

            '[[ $companyAddressBlock ]]'    => $company->legal_street ? strtoupper($company->legal_street).' '.strtoupper($company->legal_number) : null,
            '[[ $companyCapBlock ]]'        => $company->legal_zip ? strtoupper($company->legal_zip) : null,
            '[[ $companyCityBlock ]]'       => $company->legal_city ? strtoupper($company->legal_city) : null,
            '[[ $companyProvinceBlock ]]'   => $company->legal_province ? '(' . $company->legal_province . ')' : null,
            '[[ $companyCountryBlock ]]'    => $company->legal_country ? strtoupper($company->legal_country) : null,

            
            '[[ $dataLabel ]]'          => \Lang::get('invoices.Data', [], $localeClient),
            '[[ $fatturaLabel ]]'       => \Lang::get('invoices.Fattura', [], $localeClient),
            '[[ $pivaLabelCompany ]]'   => \Lang::get('invoices.P.IVA', [], $localeCompany),
            '[[ $pivaLabelClient ]]'    => \Lang::get('invoices.P.IVA', [], $localeClient),
            '[[ $fatturatoALabel ]]'    => \Lang::get('invoices.Fatturato a', [], $localeClient),

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

    /**
     * Format company P.IVA with country code prefix if client locale differs from company locale
     */
    private function formatPiva(?string $piva, ?string $clientCountry, ?string $companyCountry): ?string
    {
        if (!$piva) {
            return null;
        }

        // If client country is different from company country, add company country code prefix to company P.IVA
        if ($clientCountry && $companyCountry && strtoupper($clientCountry) !== strtoupper($companyCountry)) {
            $countryCode = strtoupper($companyCountry);
            return $countryCode . $piva;
        }

        return $piva;
    }
}