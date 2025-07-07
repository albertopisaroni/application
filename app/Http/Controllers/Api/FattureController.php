<?php

// app/Http/Controllers/Api/FattureController.php
namespace App\Http\Controllers\Api;

use App\Services\InvoiceRenderer;

use App\Http\Controllers\Controller;
use App\Http\Requests\NuovaManualeRequest;
use App\Http\Requests\NuovaPivaRequest;
use App\Jobs\SendInvoiceToSdiJob;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceNumbering;
use App\Models\InvoicePaymentSchedule;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\InvoiceTemplate;
use App\Models\Contact;






class FattureController extends Controller
{

    /**
     * @group Fatture
     * Crea una nuova fattura manuale specificando tutti i dati via API.
     *
     * @authenticated
     * @bodyParam cliente.name string required Nome del cliente.  
     * @bodyParam cliente.piva string required Partita IVA del cliente.  
     * @bodyParam cliente.address string required Indirizzo del cliente.  
     * @bodyParam cliente.cap string required CAP del cliente.  
     * @bodyParam cliente.city string required Città del cliente.  
     * @bodyParam cliente.province string required Provincia del cliente.  
     * @bodyParam cliente.country string default: IT Paese del cliente.  
     * @bodyParam cliente.sdi string nullable Codice SDI del cliente.  
     * @bodyParam cliente.pec string nullable PEC del cliente.  
     * @bodyParam cliente.email string nullable Email del cliente.  
     * @bodyParam cliente.phone string nullable Telefono del cliente.  
     *
     * @bodyParam numerazione string required Nome della numerazione da usare.  
     * @bodyParam issue_date date required Data di emissione fattura (YYYY-MM-DD).  
     * @bodyParam tipo_documento string in:TD01,TD01_ACC,TD24,TD25 Tipo di documento.  
     * @bodyParam sconto number nullable Sconto globale (importo).  
     * @bodyParam intestazione string nullable Testo da inserire nelle note di intestazione.  
     * @bodyParam note string nullable Note aggiuntive.  
     * @bodyParam metodo_pagamento string required Nome del metodo di pagamento.  
     * @bodyParam paid number nullable Importo già incassato (se presente, viene creato un pagamento).
     *
     * @bodyParam articoli array[] required Elenco degli articoli.  
     * @bodyParam articoli.*.nome string required Nome articolo.  
     * @bodyParam articoli.*.quantita number required Quantità.  
     * @bodyParam articoli.*.prezzo number required Prezzo unitario.  
     * @bodyParam articoli.*.iva number required Aliquota IVA (%).  
     * @bodyParam articoli.*.descrizione string nullable Descrizione articolo.  
     * 
     * @bodyParam emails string[] nullable Altre email a cui inviare la fattura. Example: ["info@azienda.it", "contabilita@azienda.it"]
     *
     * @bodyParam scadenze array[] nullable Scadenze di pagamento (se omesso, 30gg da issue_date).  
     * @bodyParam scadenze.*.date date required Data scadenza (YYYY-MM-DD).  
     * @bodyParam scadenze.*.value number required Valore (importo o percentuale).  
     * @bodyParam scadenze.*.type string in:percent,amount required Tipo di valore.  
     *
     * @bodyParam invia_sdi boolean default:true Se inviare la fattura al SDI.  
     *
     * @response 201 {
     *   "id": 123,
     *   "url": "https://fatture.newo.io/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx/pdf"
     * }
     */
    public function nuovaManuale(NuovaManualeRequest $request)
    {
        return $this->creaFattura($request->validated(), false);
    }

/**
 * @group Fatture
 * Crea una nuova fattura automatica tramite lookup dei dati aziendali da Partita IVA.
 *
 * @authenticated
 *
 * @bodyParam piva string required Partita IVA (con o senza prefisso "IT"). Example: 03666510791
 * @bodyParam numerazione string nullable Nome della numerazione da usare. Default: Standard. Example: Standard
 * @bodyParam issue_date date nullable Data di emissione fattura (YYYY-MM-DD). Default: oggi. Example: 2025-04-29
 * @bodyParam tipo_documento string in:TD01,TD01_ACC,TD24,TD25 nullable Tipo di documento. Default: TD01. Example: TD01
 * @bodyParam metodo_pagamento string nullable Nome del metodo di pagamento. Default: ultimo usato. Example: Revolut Pro
 * @bodyParam sconto number nullable Sconto globale (importo in Euro). Example: 20.50
 * @bodyParam intestazione string nullable Testo di intestazione. Example: Intestazione personalizzata
 * @bodyParam note string nullable Note aggiuntive. Example: Grazie per averci scelto
 * @bodyParam invia_sdi boolean default:true Se inviare la fattura allo SDI. Example: true
 *
 * @bodyParam articoli object[] required Elenco degli articoli. Example: [{"nome":"Consulenza informatica","quantita":22,"prezzo":150,"iva":0,"descrizione":"Consulenza Maggio 2025"}]
 * @bodyParam articoli.nome string required Nome articolo. Example: Consulenza informatica
 * @bodyParam articoli.quantita number required Quantità. Example: 22
 * @bodyParam articoli.prezzo number required Prezzo unitario. Example: 150
 * @bodyParam articoli.iva number required Aliquota IVA (%). Example: 0
 * @bodyParam articoli.descrizione string nullable Descrizione articolo. Example: Consulenza Maggio 2025
 * 
 * @bodyParam emails string[] nullable Altre email a cui inviare la fattura. Example: ["info@azienda.it", "contabilita@azienda.it"]
 *
 * @bodyParam scadenze object[] nullable Scadenze di pagamento (se omesso, 30gg da issue_date). Example: [{"date":"2025-05-30","value":50,"type":"percent"},{"date":"2025-06-30","value":50,"type":"percent"}]
 * @bodyParam scadenze.date date required Data scadenza (YYYY-MM-DD). Example: 2025-05-30
 * @bodyParam scadenze.value number required Importo (o percentuale se type = percent). Example: 50
 * @bodyParam scadenze.type string in:percent,amount required Tipo di valore. Example: percent
 *
 * @response 201 {
 *   "success": true,
 *   "data": {
 *     "id": 124,
 *     "url": "https://fatture.newo.io/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx/pdf"
 *   }
 * }
 */
public function nuovaPiva(NuovaPivaRequest $request)
{

    // 0) Recupera dati anagrafici via OpenAPI
    $resp = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.openapi.company.token'),
        ])
        ->get(
            env('OPENAPI_COMPANY_URL').'/IT-start/'. ltrim($request->piva, 'IT')
        )
        ->throw()
        ->json('data.0');

    $address = $resp['address']['registeredOffice'] ?? [];
    $clienteData = [
        'company_id'=> $request->company->id,
        'name'      => $resp['companyName'] ?? '',
        'piva'      => $request->piva,
        'address'   => $address['streetName'] ?? '',
        'cap'       => $address['zipCode'] ?? '',
        'city'      => $address['town'] ?? '',
        'province'  => $address['province'] ?? '',
        'country'   => 'IT',
        'sdi'       => $resp['sdiCode'] ?? '',
        'pec'       => null,
    ];
    if ($clienteData['sdi'] === '0000000') {
        $pec = Http::withHeaders([
                'Authorization'=> 'Bearer '.config('services.openapi.company.token'),
            ])
            ->get(env('OPENAPI_COMPANY_URL').'/IT-pec/'.ltrim($request->piva,'IT'))
            ->json('data.0.pec');
        $clienteData['pec'] = $pec;
    }

    // 1) Unisci i validated + i dati cliente appena costruiti
    $data = array_merge(
        $request->validated(),
        ['cliente' => $clienteData]
    );

    // 2) numerazione di default "Standard" se non indicata
    $data['numerazione'] = $data['numerazione']
        ?? InvoiceNumbering::where('company_id', $request->company->id)
            ->where('name','Standard')
            ->value('name')
        ?? 'Standard';

    // 3) issue_date di default = oggi
    $data['issue_date'] = $data['issue_date'] 
        ?? today()->toDateString();

    // 4) metodo_pagamento di default = ultimo usato per quella numerazione
    if (empty($data['metodo_pagamento'])) {
        $lastMethod = Invoice::where('company_id', $request->company->id)
            ->whereHas('numbering', fn($q) => $q->where('name',$data['numerazione']))
            ->latest('issue_date')
            ->first()
            ?->paymentMethod
            ?->name;
        if ($lastMethod) {
            $data['metodo_pagamento'] = $lastMethod;
        } else {
            // se non esiste, prendi il primo metodo di pagamento dell'azienda
            $first = PaymentMethod::where('company_id',$request->company->id)
                     ->orderBy('created_at')->first();
            $data['metodo_pagamento'] = $first?->name;
        }
    }

    // 5) normalizza l'IVA negli articoli: se mancante, metti 0
    foreach ($data['articoli'] as &$art) {
        $art['iva'] = $art['iva'] ?? 0;
    }
    unset($art);

    // 6) chiama il metodo comune per creare l'invoice e (eventualmente) inviarla
    $invoice = $this->creaFattura($data, true);

    return response()->json([
        'success' => true,
        'data' => [
            'fattura'  => $invoice->invoice_number,
            'url' => config('app.fatture_url')."/{$invoice->uuid}/pdf",
        ],
    ], 201);
}



    protected function creaFattura(array $data, bool $dispatchJob)
    {
        $company = request()->company;
        DB::beginTransaction();
        try {
            // CLIENT
            $c = $data['cliente'];
            $client = Client::updateOrCreate(
                ['company_id'=>$company->id,'piva'=>$c['piva']],
                $c
            );

  

            // NUMBERING
            $num = InvoiceNumbering::where('name',$data['numerazione'])->where('company_id', $company->id)->firstOrFail();

            // CREA FATTURA
            $invoice = Invoice::create([
                'company_id'        => $company->id,
                'client_id'         => $client->id,
                'numbering_id'      => $num->id,
                'invoice_number'    => $num->prefix . $num->getNextNumericPart(),
                'issue_date'        => $data['issue_date'],
                'fiscal_year'       => Carbon::parse($data['issue_date'])->year,
                'withholding_tax'   => 0,
                'inps_contribution' => 0,
                'payment_method_id' => PaymentMethod::where('name',$data['metodo_pagamento'])->firstOrFail()->id,
                'subtotal'          => 0, // ricalcoleremo
                'vat'               => 0,
                'total'             => 0,
                'global_discount'   => $data['sconto'] ?? 0,
                'header_notes'      => $data['intestazione'] ?? null,
                'footer_notes'      => $data['note'] ?? null,
                'document_type'     => $data['tipo_documento'] ?? 'TD01',
                'save_notes_for_future'=> false,
                'sdi_attempt'       => 1,
            ]);

            // ITEMS & RIEPILOGO
            $subtotal = $vat = 0;
            foreach($data['articoli'] as $art){
                $lineTotal = $art['quantita'] * $art['prezzo'];
                $subtotal += $lineTotal;
                $vat      += $lineTotal * ($art['iva']/100);
                $invoice->items()->create([
                    'name'         => $art['nome'],
                    'description'  => $art['descrizione'] ?? '',
                    'quantity'     => $art['quantita'],
                    'unit_price'   => $art['prezzo'],
                    'vat_rate'     => $art['iva'],
                ]);
            }
            $invoice->subtotal = $subtotal;
            $invoice->vat      = $vat;
            $invoice->total    = $subtotal + $vat - ($data['sconto'] ?? 0);
            $invoice->save();

            // PAGAMENTO DIRETTO (facoltativo)
            if (!empty($data['paid']) && $data['paid'] > 0) {
                $invoice->payments()->create([
                    'invoice_id'    => $invoice->id,
                    'amount'        => $data['paid'],
                    'payment_date'  => now(),
                    'note'          => 'Pagamento inserito al momento della creazione della fattura',
                ]);
            }

            // INCREMENTA PROGRESSIVO
            $num->increment('current_number');

            // SCADENZE
            $schedules = $data['scadenze'] ?? [];

            if (empty($schedules)) {
                $schedules = [[
                    'date'  => now()->toDateString(),
                    'value' => $invoice->total,
                    'type'  => 'amount',
                ]];
            }

            foreach($schedules as $sc){
                $raw   = (float)$sc['value'];
                $amt   = $sc['type']==='percent'
                         ? round($invoice->total * $raw/100,2)
                         : $raw;

                $invoice->paymentSchedules()->create([
                    'due_date'=> $sc['date'],
                    'amount'  => $amt,
                    'type'    => $sc['type'],
                    'percent' => $sc['type']==='percent'? $raw:null,
                ]);
            }

        // calcola se è split e qual è la dueDate per il singolo pagamento
        $split   = count($schedules) > 1;
        $dueDate = $split
            ? null
            : ($schedules[0]['date'] ?? $data['issue_date']);
        
            $normalizedItems = array_map(function(array $art): array {
                return [
                    'name'        => $art['nome'],
                    'description' => $art['descrizione'] ?? '',
                    'quantity'    => $art['quantita'],
                    'unit_price'  => $art['prezzo'],
                    'vat_rate'    => $art['iva']  ?? 0,
                ];
            }, $data['articoli']);
            
            // Normalizzo le scadenze da API al formato ["date","value","type"]
            $normalizedSchedules = array_map(function(array $sc): array {
                return [
                    'date'  => $sc['date'],
                    'value' => $sc['value'],
                    'type'  => $sc['type'],
                ];
            }, $schedules);
            
            // E ora passo i dati già normalizzati
            $renderer = new \App\Services\InvoiceRenderer(
                $invoice,
                $normalizedItems,
                $normalizedSchedules,
                $split,
                $dueDate
            );
        
            $html = $renderer->renderHtml();
            $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->output();
            $year = Carbon::parse($invoice->issue_date)->format('Y');
            $path = "clienti/{$company->slug}/fatture/{$num->id}/{$year}/{$invoice->invoice_number}.pdf";

            $encrypted = encrypt($pdf);
            Storage::disk('s3')->put($path, $encrypted);

            // 4) aggiorna il model
            $invoice->pdf_path = $path;
            $invoice->pdf_url  = config('app.fatture_url')."/{$invoice->uuid}/pdf";
            $invoice->save();

            // INVIO SDI?
            if (($data['invia_sdi'] ?? true)) {
                SendInvoiceToSdiJob::dispatch($invoice->id);
            }

            if (!empty($data['emails'])) {
                foreach ($data['emails'] as $email) {
                    Contact::firstOrCreate(
                        ['client_id' => $client->id, 'email' => $email],
                    );
                }

                $recipients = $client->contacts()->where('receives_invoice_copy', 1)->pluck('email')->toArray();

                foreach ($recipients as $email) {
                    \Mail::to($email)->send(new \App\Mail\InvoiceMail($invoice, $company));
                }

            }

            DB::commit();
            $invoice->makeHidden([
                'numbering',
                'company',
                'client',
                'company_id',
                'client_id',
                'numbering_id',
                'payment_method_id',
                'withholding_tax',
                'inps_contribution',
                'save_notes_for_future',
                'updated_at',
                'created_at',
                'id',
                'pdf_path',
            ]);
            return [
                'success' => true,
                'invoice' => $invoice,
            ];
        }
        catch(\Throwable $e){
            DB::rollBack();
            throw $e;
        }
    }



    public function fatturePdf(string $uuid)
    {
        $invoice = Invoice::where('uuid', $uuid)->firstOrFail();
    
        // Recupera il file criptato
        $encrypted = Storage::disk('s3')->get($invoice->pdf_path);
    
        // Decripta il contenuto
        $pdf = decrypt($encrypted);
    
        // Restituisci come PDF inline
        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="fattura-{$invoice->invoice_number}.pdf"');
    }

}