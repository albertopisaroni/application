<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Invoice;
use App\Models\InvoicePassive;
use App\Models\InvoicePassiveItem;
use App\Models\InvoicePassiveAttachment;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class OpenApiController extends Controller
{

    /**
     * Questo endpoint non verrà documentato.
     *
     * @hideFromAPIDocumentation
     */
    public function sdiCallback(Request $request)
    {
        $expectedToken = config('services.openapi.sdi.callback.token');
        $authHeader = $request->header('Authorization');

        // // ✅ Validazione sicurezza
        // if ($authHeader !== 'Bearer ' . $expectedToken) {
        //     Log::warning('⛔ Accesso non autorizzato al callback SDI', [
        //         'ip' => $request->ip(),
        //         'auth' => $authHeader,
        //     ]);
        //     return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        // }

        // 📦 Log della chiamata SDI
        $requestData = $request->all();
        
        Log::info('📥 Ricevuto callback SDI', [
            'ip'       => $request->ip(),
            'method'   => $request->method(),
            'query'    => $request->query(),
            'invoice_summary' => $this->extractInvoiceSummary($requestData),
        ]);

        // 🔄 Processa il callback
        try {
            $this->processInvoiceCallback($requestData);
            return response()->json(['received' => true, 'processed' => true], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('❌ Errore processamento callback SDI', [
                'error' => $e->getMessage(),
                'invoice_uuid' => $requestData['data']['invoice']['uuid'] ?? null,
            ]);
            return response()->json(['received' => true, 'processed' => false, 'error' => $e->getMessage()], Response::HTTP_OK);
        }
    }

    /**
     * Processa il callback SDI per aggiornare lo stato della fattura
     */
    private function processInvoiceCallback(array $data): void
    {
        // Validazione dati obbligatori
        if (!isset($data['event'])) {
            throw new \Exception('Dati callback incompleti: manca event');
        }

        $event = $data['event'];

        // 📋 Log dettagliato dell'evento ricevuto
        Log::info("📨 Evento SDI ricevuto: {$event}", [
            'event_type' => $event,
            'event_data' => $data['data'] ?? [],
        ]);

        // 🔍 Processa solo eventi customer-invoice (fatture attive/emesse)
        if ($event === 'customer-invoice') {
            if (!isset($data['data']['invoice']['uuid'])) {
                throw new \Exception('Dati callback incompleti: manca invoice UUID per customer-invoice');
            }
            
            $invoiceData = $data['data']['invoice'];
            $sdiUuid = $invoiceData['uuid'];

            // Trova la fattura nel database
            $invoice = Invoice::where('sdi_uuid', $sdiUuid)->first();
            
            if (!$invoice) {
                throw new \Exception("Fattura attiva non trovata con SDI UUID: {$sdiUuid}");
            }

            // Processa l'evento customer-invoice
            $this->processCustomerInvoiceEvent($invoice, $invoiceData);
            
        } elseif ($event === 'supplier-invoice') {
            // 📥 Fatture passive/spese - salva nel database
            if (!isset($data['data']['invoice']['uuid'])) {
                throw new \Exception('Dati callback incompleti: manca invoice UUID per supplier-invoice');
            }
            
            $this->processSupplierInvoiceEvent($data['data']['invoice']);
            
        } elseif ($event === 'customer-notification') {
            // 📨 Notifiche per fatture attive (ricevute di consegna, ecc.)
            $this->processCustomerNotificationEvent($data['data'] ?? []);
            
        } elseif ($event === 'legal-storage-receipt') {
            // 📋 Ricevute di conservazione sostitutiva
            $this->processLegalStorageReceiptEvent($data['data'] ?? []);
            
        } else {
            // 📋 Altri eventi - solo log
            Log::info("📋 Evento SDI non gestito: {$event}", [
                'event_data' => $data['data'] ?? [],
                'action' => 'logged_only'
            ]);
        }
    }

    /**
     * Processa l'evento customer-invoice (fattura ricevuta/processata)
     */
    private function processCustomerInvoiceEvent(Invoice $invoice, array $invoiceData): void
    {
        $payload = $invoiceData['payload'] ?? [];
        $filename = $invoiceData['filename'] ?? null;
        
        // 🔍 Determina lo stato in base al contenuto
        $status = $this->determineInvoiceStatus($invoiceData, $payload);
        
        // 📝 Aggiorna la fattura con i dati ricevuti
        $updateData = [
            'sdi_status' => $status,
            'sdi_received_at' => now(),
            'data_accoglienza_file' => now(),
        ];

        // Se ci sono errori, salva anche quelli
        if ($status === 'error' || $status === 'rejected') {
            $updateData['sdi_error'] = $this->extractErrorFromPayload($payload);
            $updateData['sdi_error_description'] = $this->extractErrorDescriptionFromPayload($payload);
        }

        $invoice->update($updateData);

        // 📊 Log differenziato per tipo di stato
        $this->logInvoiceProcessingResult($invoice, $status, $filename);

        // 🔔 Gestisci notifiche in base allo stato
        $this->handleStatusBasedNotifications($invoice, $status);
    }

    /**
     * Determina lo stato della fattura in base ai dati ricevuti
     */
    private function determineInvoiceStatus(array $invoiceData, array $payload): string
    {
        // Controlla se ci sono errori nel payload
        if (isset($payload['errors']) && !empty($payload['errors'])) {
            return 'error';
        }

        // Controlla se la fattura è stata rifiutata
        if (isset($payload['status']) && in_array($payload['status'], ['rejected', 'scartata', 'rifiutata'])) {
            return 'rejected';
        }

        // Se il payload contiene la struttura completa della fattura elettronica, 
        // significa che è stata accettata e processata
        if (isset($payload['fattura_elettronica_header']) && isset($payload['fattura_elettronica_body'])) {
            return 'received';
        }

        // Fallback: se non riusciamo a determinare, assume processed
        return 'processed';
    }

    /**
     * Estrae codice errore dal payload
     */
    private function extractErrorFromPayload(array $payload): ?string
    {
        return $payload['error_code'] ?? $payload['errors'][0]['code'] ?? null;
    }

    /**
     * Estrae descrizione errore dal payload
     */
    private function extractErrorDescriptionFromPayload(array $payload): ?string
    {
        return $payload['error_message'] ?? 
               $payload['errors'][0]['message'] ?? 
               $payload['error_description'] ?? 
               'Errore non specificato';
    }

    /**
     * Log differenziato per tipo di risultato
     */
    private function logInvoiceProcessingResult(Invoice $invoice, string $status, ?string $filename): void
    {
        $baseData = [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'sdi_uuid' => $invoice->sdi_uuid,
            'filename' => $filename,
            'status' => $status,
        ];

        switch ($status) {
            case 'received':
                Log::info('✅ Fattura SDI ricevuta e accettata', $baseData);
                break;
            
            case 'rejected':
                Log::warning('❌ Fattura SDI rifiutata/scartata', array_merge($baseData, [
                    'error_code' => $invoice->sdi_error,
                    'error_description' => $invoice->sdi_error_description,
                ]));
                break;
            
            case 'error':
                Log::error('🚨 Errore processamento fattura SDI', array_merge($baseData, [
                    'error_code' => $invoice->sdi_error,
                    'error_description' => $invoice->sdi_error_description,
                ]));
                break;
            
            default:
                Log::info("📋 Fattura SDI processata con stato: {$status}", $baseData);
        }
    }

    /**
     * Gestisce notifiche specifiche per stato
     */
    private function handleStatusBasedNotifications(Invoice $invoice, string $status): void
    {
        // TODO: Implementare notifiche specifiche
        // - Email di conferma per 'received'
        // - Email di alert per 'rejected' o 'error'
        // - Webhook per sistemi esterni
        // - Notifiche push per amministratori
    }

    /**
     * Processa l'evento customer-notification (notifiche per fatture attive)
     */
    private function processCustomerNotificationEvent(array $eventData): void
    {
        $notification = $eventData['notification'] ?? [];
        $invoiceUuid = $notification['invoice_uuid'] ?? null;
        
        if (!$invoiceUuid) {
            Log::warning("⚠️ Notifica customer senza invoice_uuid", [
                'notification_data' => $notification
            ]);
            return;
        }

        // Trova la fattura nel database
        $invoice = Invoice::where('sdi_uuid', $invoiceUuid)->first();
        if (!$invoice) {
            Log::warning("⚠️ Fattura non trovata per notifica customer", [
                'invoice_uuid' => $invoiceUuid,
                'notification_type' => $notification['type'] ?? 'unknown'
            ]);
            return;
        }

        $notificationType = $notification['type'] ?? 'unknown';
        $fileName = $notification['file_name'] ?? null;
        $message = $notification['message'] ?? [];
        
        // Aggiorna la fattura con i dati della notifica
        $updateData = [];
        
        switch ($notificationType) {
            case 'RC': // Ricevuta di Consegna
                $updateData['sdi_status'] = Invoice::SDI_STATUS_DELIVERED;
                $updateData['sdi_received_at'] = now();
                $updateData['notification_type'] = 'RC';
                $updateData['notification_file_name'] = $fileName;
                
                // Estrai informazioni dalla ricevuta nei campi dedicati
                if (!empty($message)) {
                    $updateData['sdi_identificativo'] = $message['identificativo_sdi'] ?? null;
                    $updateData['sdi_data_ricezione'] = !empty($message['data_ora_ricezione']) 
                        ? \Carbon\Carbon::parse($message['data_ora_ricezione']) 
                        : null;
                    $updateData['sdi_data_consegna'] = !empty($message['data_ora_consegna']) 
                        ? \Carbon\Carbon::parse($message['data_ora_consegna']) 
                        : null;
                    $updateData['sdi_message_id'] = $message['message_id'] ?? null;
                    $updateData['sdi_destinatario'] = $message['destinatario'] ?? null;
                }
                
                Log::info("✅ Ricevuta di consegna processata", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'sdi_uuid' => $invoiceUuid,
                    'file_name' => $fileName,
                    'message_id' => $message['message_id'] ?? null,
                    'consegna' => $message['data_ora_consegna'] ?? null
                ]);
                break;
                
            case 'NS': // Notifica di Scarto
                $updateData['sdi_status'] = Invoice::SDI_STATUS_REJECTED;
                $updateData['sdi_error'] = 'NS';
                $updateData['sdi_error_description'] = 'Notifica di Scarto ricevuta';
                $updateData['notification_type'] = 'NS';
                $updateData['notification_file_name'] = $fileName;
                
                // Per NS, salviamo i dettagli dell'errore nei campi dedicati
                if (!empty($message)) {
                    $updateData['sdi_identificativo'] = $message['identificativo_sdi'] ?? null;
                    $updateData['sdi_message_id'] = $message['message_id'] ?? null;
                }
                
                Log::warning("❌ Notifica di scarto ricevuta", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'sdi_uuid' => $invoiceUuid,
                    'file_name' => $fileName,
                    'message' => $message
                ]);
                break;
                
            case 'MC': // Mancata Consegna
                $updateData['sdi_status'] = Invoice::SDI_STATUS_DELIVERY_FAILED;
                $updateData['sdi_error'] = 'MC';
                $updateData['sdi_error_description'] = 'Mancata Consegna';
                $updateData['notification_type'] = 'MC';
                $updateData['notification_file_name'] = $fileName;
                
                // Per MC, salviamo i dettagli nei campi dedicati
                if (!empty($message)) {
                    $updateData['sdi_identificativo'] = $message['identificativo_sdi'] ?? null;
                    $updateData['sdi_message_id'] = $message['message_id'] ?? null;
                }
                
                Log::warning("⚠️ Mancata consegna segnalata", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'sdi_uuid' => $invoiceUuid,
                    'file_name' => $fileName,
                    'message' => $message
                ]);
                break;
                
            default:
                Log::info("📨 Notifica customer ricevuta", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'sdi_uuid' => $invoiceUuid,
                    'notification_type' => $notificationType,
                    'file_name' => $fileName,
                    'message' => $message
                ]);
                
                // Per tipi sconosciuti, salva solo il timestamp
                $updateData['sdi_received_at'] = now();
                break;
        }
        
        if (!empty($updateData)) {
            $invoice->update($updateData);
        }
    }

    /**
     * Processa l'evento legal-storage-receipt (ricevute conservazione sostitutiva)
     */
    private function processLegalStorageReceiptEvent(array $eventData): void
    {
        $receiptUuid = $eventData['uuid'] ?? null;
        $invoiceUuid = $eventData['invoice_uuid'] ?? $eventData['object_id'] ?? null;
        $status = $eventData['status'] ?? 'unknown';
        $message = $eventData['message'] ?? null;
        
        if (!$invoiceUuid) {
            Log::warning("⚠️ Ricevuta conservazione senza invoice_uuid", [
                'receipt_data' => $eventData
            ]);
            return;
        }

        // Trova la fattura nel database
        $invoice = Invoice::where('sdi_uuid', $invoiceUuid)->first();
        if (!$invoice) {
            Log::warning("⚠️ Fattura non trovata per ricevuta conservazione", [
                'invoice_uuid' => $invoiceUuid,
                'receipt_uuid' => $receiptUuid,
                'status' => $status
            ]);
            return;
        }

        // Aggiorna la fattura con lo stato di conservazione (NON tocca sdi_status)
        $updateData = [];
        
        switch ($status) {
            case 'stored':
                // Conservazione completata con successo
                $updateData['legal_storage_status'] = Invoice::LEGAL_STORAGE_STATUS_STORED;
                $updateData['legal_storage_uuid'] = $receiptUuid;
                $updateData['legal_storage_completed_at'] = $eventData['updated_at'] ?? now();
                $updateData['legal_storage_error'] = null; // Pulisci eventuali errori precedenti
                
                Log::info("✅ Conservazione sostitutiva completata", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'sdi_uuid' => $invoiceUuid,
                    'receipt_uuid' => $receiptUuid,
                    'conservazione_date' => $updateData['legal_storage_completed_at']
                ]);
                break;
                
            case 'error':
            case 'failed':
                // Errore nella conservazione
                $updateData['legal_storage_status'] = Invoice::LEGAL_STORAGE_STATUS_FAILED;
                $updateData['legal_storage_uuid'] = $receiptUuid;
                $updateData['legal_storage_error'] = $message ?: 'Errore sconosciuto nella conservazione';
                
                Log::error("❌ Errore conservazione sostitutiva", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'sdi_uuid' => $invoiceUuid,
                    'receipt_uuid' => $receiptUuid,
                    'error_message' => $updateData['legal_storage_error']
                ]);
                break;
                
            default:
                // Status sconosciuto, logga solo senza aggiornare
                Log::info("📋 Ricevuta conservazione con status sconosciuto", [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'sdi_uuid' => $invoiceUuid,
                    'receipt_uuid' => $receiptUuid,
                    'status' => $status,
                    'message' => $message
                ]);
                
                // Per status sconosciuti, salviamo almeno l'UUID per tracciabilità
                $updateData['legal_storage_uuid'] = $receiptUuid;
                break;
        }
        
        if (!empty($updateData)) {
            $invoice->update($updateData);
        }
    }

    /**
     * Estrae un summary dettagliato dell'invoice dal payload
     */
    private function extractInvoiceSummary(array $requestData): array
    {
        $summary = [
            'event' => $requestData['event'] ?? null,
            'invoice_uuid' => $requestData['data']['invoice']['uuid'] ?? null,
            'filename' => $requestData['data']['invoice']['filename'] ?? null,
            'created_at' => $requestData['data']['invoice']['created_at'] ?? null,
        ];

        $payload = $requestData['data']['invoice']['payload'] ?? [];
        
        if (!empty($payload)) {
            $summary['payload_keys'] = array_keys($payload);
            
            // Estrai dati dall'header
            $header = $payload['fattura_elettronica_header'] ?? [];
            if (!empty($header)) {
                $summary['cedente'] = $header['cedente_prestatore']['dati_anagrafici']['anagrafica']['denominazione'] ?? null;
                $summary['cessionario'] = $header['cessionario_committente']['dati_anagrafici']['anagrafica']['denominazione'] ?? null;
            }
            
            // Estrai dati dal body
            $body = $payload['fattura_elettronica_body'] ?? [];
            if (!empty($body) && isset($body[0])) {
                $firstBody = $body[0];
                
                // Dati generali documento
                $docData = $firstBody['dati_generali']['dati_generali_documento'] ?? [];
                if (!empty($docData)) {
                    $summary['numero_documento'] = $docData['numero'] ?? null;
                    $summary['data_documento'] = $docData['data'] ?? null;
                    $summary['tipo_documento'] = $docData['tipo_documento'] ?? null;
                    $summary['divisa'] = $docData['divisa'] ?? null;
                    $summary['importo_totale'] = $docData['importo_totale_documento'] ?? null;
                }
                
                // Dati beni/servizi - riepilogo IVA
                $beniServizi = $firstBody['dati_beni_servizi'] ?? [];
                if (!empty($beniServizi)) {
                    $riepiloghi = $beniServizi['dati_riepilogo'] ?? [];
                    $summary['iva_dettagli'] = [];
                    
                    foreach ($riepiloghi as $riepilogo) {
                        $summary['iva_dettagli'][] = [
                            'aliquota' => $riepilogo['aliquota_iva'] ?? null,
                            'imponibile' => $riepilogo['imponibile_importo'] ?? null,
                            'imposta' => $riepilogo['imposta'] ?? null,
                        ];
                    }
                    
                    // Numero righe
                    $righe = $beniServizi['dettaglio_linee'] ?? [];
                    $summary['numero_righe'] = count($righe);
                }
            }
        }

        return $summary;
    }

    /**
     * Processa l'evento supplier-invoice (fattura passiva ricevuta)
     */
    private function processSupplierInvoiceEvent(array $invoiceData): void
    {
        $payload = $invoiceData['payload'] ?? [];
        $sdiUuid = $invoiceData['uuid'];
        
        // Verifica se la fattura esiste già
        $existingInvoice = InvoicePassive::where('sdi_uuid', $sdiUuid)->first();
        if ($existingInvoice) {
            Log::info("📋 Fattura passiva già esistente", [
                'sdi_uuid' => $sdiUuid,
                'invoice_id' => $existingInvoice->id,
            ]);
            return;
        }

        DB::beginTransaction();
        try {
            // Estrai dati dall'XML payload
            $invoiceDetails = $this->extractInvoiceDetailsFromPayload($payload);
            
            // Trova o crea il fornitore
            $supplier = $this->findOrCreateSupplier($invoiceDetails['supplier_data']);
            
            // Estrai la partita IVA del cessionario committente dall'XML
            $cessionarioPiva = null;
            if (isset($payload['fattura_elettronica_header']['cessionario_committente']['dati_anagrafici']['id_fiscale_iva']['id_codice'])) {
                $cessionarioPiva = $payload['fattura_elettronica_header']['cessionario_committente']['dati_anagrafici']['id_fiscale_iva']['id_codice'];
            }
            
            if (!$cessionarioPiva) {
                throw new \Exception('Partita IVA del cessionario committente non trovata nell\'XML');
            }
            
            // Trova la company tramite partita IVA
            $company = Company::where('piva', $cessionarioPiva)->first();
            if (!$company) {
                throw new \Exception('Company con P.IVA ' . $cessionarioPiva . ' non trovata nel sistema');
            }

            // Crea la fattura passiva
            $passiveInvoice = InvoicePassive::create([
                'company_id' => $company->id,
                'supplier_id' => $supplier->id,
                'invoice_number' => $invoiceDetails['invoice_number'],
                'document_type' => $invoiceDetails['document_type'],
                'issue_date' => $invoiceDetails['issue_date'],
                'data_accoglienza_file' => now(),
                'fiscal_year' => date('Y', strtotime($invoiceDetails['issue_date'])),
                'subtotal' => $invoiceDetails['subtotal'],
                'vat' => $invoiceDetails['vat'],
                'total' => $invoiceDetails['total'],
                'sdi_uuid' => $sdiUuid,
                'sdi_filename' => $invoiceData['filename'] ?? null,
                'sdi_status' => 'received',
                'sdi_received_at' => now(),
                'xml_payload' => $payload,
                'imported_from_callback' => true,
            ]);

            // Crea le righe della fattura
            foreach ($invoiceDetails['items'] as $itemData) {
                InvoicePassiveItem::create([
                    'invoice_passive_id' => $passiveInvoice->id,
                    'line_number' => $itemData['line_number'],
                    'name' => $itemData['name'],
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_of_measure' => $itemData['unit_of_measure'],
                    'unit_price' => $itemData['unit_price'],
                    'line_total' => $itemData['line_total'],
                    'vat_rate' => $itemData['vat_rate'],
                    'vat_amount' => $itemData['vat_amount'],
                ]);
            }

            // Gestisce gli allegati se presenti
            $attachmentsProcessed = $this->processAttachments($passiveInvoice, $payload, $invoiceData);

            // Aggiorna il riassunto degli allegati nella tabella principale
            if ($attachmentsProcessed > 0) {
                $passiveInvoice->refresh();
                $passiveInvoice->updateAttachmentsSummary();
            }

            DB::commit();

            Log::info("✅ Fattura passiva creata con successo", [
                'invoice_id' => $passiveInvoice->id,
                'supplier_name' => $supplier->name,
                'invoice_number' => $passiveInvoice->invoice_number,
                'total' => $passiveInvoice->total,
                'items_count' => count($invoiceDetails['items']),
                'attachments_count' => $attachmentsProcessed,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Errore creazione fattura passiva", [
                'sdi_uuid' => $sdiUuid,
                'error' => $e->getMessage(),
                'payload_summary' => array_keys($payload),
            ]);
            throw $e;
        }
    }

    /**
     * Estrae i dettagli della fattura dal payload XML
     */
    private function extractInvoiceDetailsFromPayload(array $payload): array
    {
        $header = $payload['fattura_elettronica_header'] ?? [];
        $body = $payload['fattura_elettronica_body'][0] ?? [];

        // Dati fornitore (cedente prestatore)
        $cedente = $header['cedente_prestatore'] ?? [];
        $supplierData = [
            'name' => $cedente['dati_anagrafici']['anagrafica']['denominazione'] ?? 'Fornitore Sconosciuto',
            'vat_number' => $cedente['dati_anagrafici']['id_fiscale_iva']['id_codice'] ?? null,
            'tax_code' => $cedente['dati_anagrafici']['codice_fiscale'] ?? null,
            'address' => $cedente['sede']['indirizzo'] ?? null,
            'city' => $cedente['sede']['comune'] ?? null,
            'postal_code' => $cedente['sede']['cap'] ?? null,
            'province' => $cedente['sede']['provincia'] ?? null,
            'country' => $cedente['sede']['nazione'] ?? 'IT',
            'email' => $cedente['contatti']['email'] ?? null,
        ];

        // Dati documento
        $docData = $body['dati_generali']['dati_generali_documento'] ?? [];
        
        // Calcola totali
        $subtotal = 0;
        $vat = 0;
        $items = [];

        $lines = $body['dati_beni_servizi']['dettaglio_linee'] ?? [];
        foreach ($lines as $index => $line) {
            $lineTotal = floatval($line['prezzo_totale'] ?? 0);
            $vatRate = floatval($line['aliquota_iva'] ?? 0);
            $vatAmount = round($lineTotal * ($vatRate / 100), 2);
            
            $subtotal += $lineTotal;
            $vat += $vatAmount;

            $items[] = [
                'line_number' => $line['numero_linea'] ?? ($index + 1),
                'name' => $line['descrizione'] ?? 'Prodotto/Servizio',
                'description' => $line['descrizione'] ?? null,
                'quantity' => floatval($line['quantita'] ?? 1),
                'unit_of_measure' => $line['unita_misura'] ?? null,
                'unit_price' => floatval($line['prezzo_unitario'] ?? 0),
                'line_total' => $lineTotal,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
            ];
        }

        return [
            'supplier_data' => $supplierData,
            'invoice_number' => $docData['numero'] ?? 'N/A',
            'document_type' => $docData['tipo_documento'] ?? 'TD01',
            'issue_date' => $docData['data'] ?? now()->format('Y-m-d'),
            'subtotal' => $subtotal,
            'vat' => $vat,
            'total' => floatval($docData['importo_totale_documento'] ?? ($subtotal + $vat)),
            'items' => $items,
        ];
    }

    /**
     * Trova o crea il fornitore
     */
    private function findOrCreateSupplier(array $supplierData): Client
    {
        // Cerca per P.IVA se disponibile
        if (!empty($supplierData['vat_number'])) {
            $supplier = Client::where('piva', $supplierData['vat_number'])->first();
            if ($supplier) {
                return $supplier;
            }
        }

        // Cerca per nome se non trovato per P.IVA
        $supplier = Client::where('name', $supplierData['name'])->first();
        if ($supplier) {
            return $supplier;
        }

        // Crea nuovo fornitore
        return Client::create([
            'name' => $supplierData['name'],
            'piva' => $supplierData['vat_number'],
            'address' => $supplierData['address'],
            'city' => $supplierData['city'],
            'cap' => $supplierData['postal_code'],
            'province' => $supplierData['province'],
            'country' => $supplierData['country'],
            'company_id' => Company::first()->id, // Associa alla prima company
            'active' => true,
            'hidden' => false,
        ]);
    }

    /**
     * Processa gli allegati della fattura passiva (PDF, XML, etc.)
     */
    private function processAttachments(InvoicePassive $invoice, array $payload, array $invoiceData): int
    {
        $attachmentsProcessed = 0;

        // 1. Controlla se ci sono allegati nel payload XML
        $body = $payload['fattura_elettronica_body'][0] ?? [];
        $allegati = $body['allegati'] ?? [];

        foreach ($allegati as $index => $allegato) {
            try {
                if (!empty($allegato['nome_attachment']) && !empty($allegato['attachment'])) {
                    $attachmentProcessed = $this->processXmlAttachment(
                        $invoice, 
                        $allegato, 
                        $index + 1
                    );
                    if ($attachmentProcessed) {
                        $attachmentsProcessed++;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("⚠️ Errore processamento allegato XML", [
                    'invoice_id' => $invoice->id,
                    'attachment_index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 2. Controlla se ci sono allegati/PDF esterni (via callback data aggiuntivi)
        if (isset($invoiceData['attachments']) && is_array($invoiceData['attachments'])) {
            foreach ($invoiceData['attachments'] as $attachmentData) {
                try {
                    $attachmentProcessed = $this->processExternalAttachment($invoice, $attachmentData);
                    if ($attachmentProcessed) {
                        $attachmentsProcessed++;
                    }
                } catch (\Exception $e) {
                    Log::warning("⚠️ Errore processamento allegato esterno", [
                        'invoice_id' => $invoice->id,
                        'attachment_data' => $attachmentData,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $attachmentsProcessed;
    }

    /**
     * Processa allegato dal XML (base64 embedded)
     */
    private function processXmlAttachment(InvoicePassive $invoice, array $allegatoData, int $index): bool
    {
        $nomeFile = $allegatoData['nome_attachment'];
        $base64Content = $allegatoData['attachment'];
        $descrizione = $allegatoData['descrizione_attachment'] ?? null;

        // Decodifica base64
        $fileContent = base64_decode($base64Content);
        if ($fileContent === false) {
            throw new \Exception("Impossibile decodificare allegato base64: {$nomeFile}");
        }

        // Determina tipo file
        $extension = strtolower(pathinfo($nomeFile, PATHINFO_EXTENSION));
        $mimeType = $this->getMimeTypeFromExtension($extension);
        $attachmentType = $this->getAttachmentTypeFromExtension($extension);

        // Path S3
        $company = $invoice->company;
        $year = $invoice->fiscal_year;
        $s3Path = "clienti/{$company->slug}/spese/{$year}/{$invoice->id}/allegati/{$nomeFile}";

        // Cripta e carica su S3
        $encryptedContent = encrypt($fileContent);
        Storage::disk('s3')->put($s3Path, $encryptedContent);

        // Crea record allegato
        InvoicePassiveAttachment::create([
            'invoice_passive_id' => $invoice->id,
            'filename' => $nomeFile,
            'mime_type' => $mimeType,
            'file_extension' => $extension,
            'file_size' => strlen($fileContent),
            'file_hash' => md5($fileContent),
            's3_path' => $s3Path,
            'is_encrypted' => true,
            'attachment_type' => $attachmentType,
            'description' => $descrizione,
            'metadata' => [
                'source' => 'xml_embedded',
                'attachment_index' => $index,
            ],
            'is_primary' => $attachmentType === 'pdf' && $index === 1,
            'is_processed' => true,
        ]);

        Log::info("📎 Allegato XML salvato", [
            'invoice_id' => $invoice->id,
            'filename' => $nomeFile,
            'size' => strlen($fileContent),
            'type' => $attachmentType,
            's3_path' => $s3Path,
        ]);

        return true;
    }

    /**
     * Processa allegato esterno (URL download)
     */
    private function processExternalAttachment(InvoicePassive $invoice, array $attachmentData): bool
    {
        $url = $attachmentData['url'] ?? null;
        $filename = $attachmentData['filename'] ?? 'allegato.pdf';
        $description = $attachmentData['description'] ?? null;

        if (empty($url)) {
            throw new \Exception("URL allegato mancante");
        }

        // Download del file
        $response = Http::timeout(30)->get($url);
        if (!$response->successful()) {
            throw new \Exception("Impossibile scaricare allegato da: {$url}");
        }

        $fileContent = $response->body();
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeType = $this->getMimeTypeFromExtension($extension);
        $attachmentType = $this->getAttachmentTypeFromExtension($extension);

        // Path S3
        $company = $invoice->company;
        $year = $invoice->fiscal_year;
        $s3Path = "fatture-passive/{$company->slug}/{$year}/{$invoice->id}/allegati/{$filename}";

        // Cripta e carica su S3
        $encryptedContent = encrypt($fileContent);
        Storage::disk('s3')->put($s3Path, $encryptedContent);

        // Crea record allegato
        InvoicePassiveAttachment::create([
            'invoice_passive_id' => $invoice->id,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'file_extension' => $extension,
            'file_size' => strlen($fileContent),
            'file_hash' => md5($fileContent),
            's3_path' => $s3Path,
            'is_encrypted' => true,
            'attachment_type' => $attachmentType,
            'description' => $description,
            'metadata' => [
                'source' => 'external_download',
                'original_url' => $url,
            ],
            'is_primary' => $attachmentType === 'pdf',
            'is_processed' => true,
        ]);

        Log::info("📎 Allegato esterno salvato", [
            'invoice_id' => $invoice->id,
            'filename' => $filename,
            'size' => strlen($fileContent),
            'type' => $attachmentType,
            's3_path' => $s3Path,
            'source_url' => $url,
        ]);

        return true;
    }

    /**
     * Determina il MIME type dall'estensione
     */
    private function getMimeTypeFromExtension(string $extension): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'xml' => 'application/xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Determina il tipo di allegato dall'estensione
     */
    private function getAttachmentTypeFromExtension(string $extension): string
    {
        $types = [
            'pdf' => 'pdf',
            'xml' => 'xml',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'doc' => 'document',
            'docx' => 'document',
            'txt' => 'document',
            'zip' => 'archive',
        ];

        return $types[$extension] ?? 'other';
    }
}