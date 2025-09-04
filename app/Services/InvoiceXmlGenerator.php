<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentMethod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class InvoiceXmlGenerator
{
    /**
     * Genera lo XML FatturaPA a partire da un Invoice Eloquent
     */
    public function generate(Invoice $invoice): string
    {
        // ----------------------------
        // 1) PAYMENT METHOD + COMPANY
        // ----------------------------
        $paymentMethod = $invoice->paymentMethod;              // belongsTo
        $sdiMode       = $paymentMethod?->sdi_code   ?? 'MP05';
        $iban          = $paymentMethod?->iban       ?? '';

        $company = $invoice->company;                         // belongsTo
        $companyName = htmlspecialchars(substr(trim($company->legal_name), 0, 80), ENT_XML1, 'UTF-8');
        $isForf  = (bool) $company->forfettario;

        $codiceFiscale = trim($company->codice_fiscale);
        $cfXml = ($codiceFiscale !== '' && $codiceFiscale !== $company->piva)
            ? "<CodiceFiscale>" . htmlspecialchars($codiceFiscale, ENT_XML1, 'UTF-8') . "</CodiceFiscale>"
            : '';

        // ----------------------------
        // 2) CLIENT, ITEMS, DATE DOC
        // ----------------------------
        $client   = $invoice->client;                         // belongsTo
        $items    = $invoice->items;                          // hasMany
        $num      = $invoice->invoice_number;
        $dateDoc  = $invoice->issue_date->format('Y-m-d');

        // ---------------------------------------
        // 3) BLOCCO DESTINATARIO (SDI o PEC)
        // ---------------------------------------
        $sdi = trim($client->sdi ?? '');
        $pec = trim($client->pec ?? '');
        $codiceValido = $sdi !== '0000000' && preg_match('/^[A-Za-z0-9]{7}$/', $sdi);

        $destinatario = $codiceValido
            ? "<CodiceDestinatario>" . htmlspecialchars($sdi, ENT_XML1, 'UTF-8') . "</CodiceDestinatario>"
            : "<CodiceDestinatario>0000000</CodiceDestinatario>"
              . ($pec ? "<PECDestinatario>" . htmlspecialchars($pec, ENT_XML1, 'UTF-8') . "</PECDestinatario>" : '');

        // ----------------------------
        // 4) REA E CONTATTI CEDENTE
        // ----------------------------
        $reaXml = '';

        $regime = $isForf
            ? 'RF19'
            : ($company->regime_fiscale ?: 'RF01');

        if (! empty($company->rea_ufficio) && ! empty($company->rea_numero)) {
            $reaXml = "
            <IscrizioneREA>
              <Ufficio>" . htmlspecialchars($company->rea_ufficio, ENT_XML1, 'UTF-8') . "</Ufficio>
              <NumeroREA>" . htmlspecialchars($company->rea_numero, ENT_XML1, 'UTF-8') . "</NumeroREA>"
              . (! empty($company->rea_stato_liquidazione)
                  ? "<StatoLiquidazione>" . htmlspecialchars($company->rea_stato_liquidazione, ENT_XML1, 'UTF-8') . "</StatoLiquidazione>"
                  : '')
            ."</IscrizioneREA>";
        }

        $contactsXml = '';
        if (! empty($company->email)) {
            $contactsXml = "
            <Contatti>
              <Email>" . htmlspecialchars($company->email, ENT_XML1, 'UTF-8') . "</Email>
            </Contatti>";
        }

        // ---------------------------------------
        // 5) COSTRUZIONE LINEE E RIEPILOGO IVA
        // ---------------------------------------
        $linee = '';
        $riepiloghi = [];
        foreach ($items as $i => $row) {
            $qty   = max(0, (float) $row->quantity);
            $unit  = max(0, (float) $row->unit_price);
            $aliq  = $isForf ? 0 : max(0, (float) $row->vat_rate);
            $tot   = round($qty * $unit, 2);



            $descr = htmlspecialchars(trim(
                ($row->name ?: 'Articolo') .
                ($row->description ? " - {$row->description}" : '')
            ), ENT_XML1, 'UTF-8');

            $linee .= "
            <DettaglioLinee>
              <NumeroLinea>".($i+1)."</NumeroLinea>
              <Descrizione>{$descr}</Descrizione>
              <Quantita>".number_format($qty,5,'.','')."</Quantita>
              <PrezzoUnitario>".number_format($unit,2,'.','')."</PrezzoUnitario>
              <PrezzoTotale>".number_format($tot,2,'.','')."</PrezzoTotale>
              <AliquotaIVA>".number_format($aliq,2,'.','')."</AliquotaIVA>"
              .($aliq==0 ? "<Natura>N2.2</Natura>" : '')
            ."</DettaglioLinee>";

            $riepiloghi[(string)$aliq] = ($riepiloghi[(string)$aliq] ?? 0) + $tot;
        }

        $riepilogoXml = '';
        foreach ($riepiloghi as $aliq => $imp) {
            $tax = $aliq==0 ? 0 : $imp * ($aliq/100);
            $riepilogoXml .= "
            <DatiRiepilogo>
              <AliquotaIVA>".number_format($aliq,2,'.','')."</AliquotaIVA>"
              .($aliq==0?"<Natura>N2.2</Natura>":'')
            ."<ImponibileImporto>".number_format($imp,2,'.','')."</ImponibileImporto>
              <Imposta>".number_format($tax,2,'.','')."</Imposta>
              <EsigibilitaIVA>I</EsigibilitaIVA>";
            if ($aliq==0) {
                $riepilogoXml .= "
              <RiferimentoNormativo>VENDITE CONTRIBUENTI FORFAIT ART.1 C.54-89 L190/14 #N020502#</RiferimentoNormativo>";
            }
            $riepilogoXml .= "
            </DatiRiepilogo>";
        }

        // ----------------------------
        // 6) DDT (se TD24/TD25)
        // ----------------------------
        $tipoDoc = $invoice->document_type === 'TD01_ACC' ? 'TD01' : $invoice->document_type;
        $ddtXml = '';
        if (in_array($tipoDoc,['TD24','TD25']) && $invoice->ddt_number) {
            $ddtXml = "
            <DatiDDT>
              <NumeroDDT>" . htmlspecialchars($invoice->ddt_number, ENT_XML1, 'UTF-8') . "</NumeroDDT>
              <DataDDT>{$invoice->ddt_date}</DataDDT>
            </DatiDDT>";
        }

        // ------------------------------------------
        // 7) SCONTO, TOTALE, BOLLO, CAUSALE
        // ------------------------------------------
        $totDoc = number_format($invoice->total,2,'.','');
            
        $scontoXml = '';
        if ($invoice->global_discount > 0) {
            $scontoXml = "
            <ScontoMaggiorazione>
              <Tipo>SC</Tipo>
              <Importo>".number_format($invoice->global_discount,2,'.','')."</Importo>
            </ScontoMaggiorazione>";
        }
        
        $hasOnlyZeroIva = $invoice->items->every(function ($item) {
            return floatval($item->vat_rate) == 0;
        });

        $bollo = ($hasOnlyZeroIva && $invoice->total > 77.47)
            ? '<DatiBollo><BolloVirtuale>SI</BolloVirtuale><ImportoBollo>2.00</ImportoBollo></DatiBollo>'
            : '';

        $dataForm = $invoice->issue_date->format('d/m/Y');
        
        // Gestione causale per nota di credito
        if ($tipoDoc === 'TD04') {
            $causale = "Nota di credito ({$tipoDoc}) del {$dataForm} N.ro {$num}";
        } else {
            $causale = "Fattura immediata ({$tipoDoc}) del {$dataForm} N.ro {$num}";
        }

        // Sezione DatiFattureCollegate per note di credito
        $datiFattureCollegateXml = '';
        if ($tipoDoc === 'TD04' && $invoice->originalInvoice) {
            $originalInvoice = $invoice->originalInvoice;
            $datiFattureCollegateXml = "
            <DatiFattureCollegate>
              <IdDocumento>" . htmlspecialchars($originalInvoice->invoice_number, ENT_XML1, 'UTF-8') . "</IdDocumento>
              <Data>{$originalInvoice->issue_date->format('Y-m-d')}</Data>
            </DatiFattureCollegate>";
        }

        // ---------------------------------------
        // 8) DATI PAGAMENTO + CONTROLLO DATE
        // ---------------------------------------
        $datiPagamento = '<CondizioniPagamento>TP02</CondizioniPagamento>';

        // cicla sulle scadenze salvate
        foreach ($invoice->paymentSchedules as $sched) {
            // data di scadenza
            $due = Carbon::parse($sched->due_date);
            $dueStr = $due->format('Y-m-d');
        
            // importo già calcolato in fase di salvataggio
            $importo = number_format($sched->amount, 2, '.', '');
        
            $datiPagamento .= "
            <DettaglioPagamento>
              <ModalitaPagamento>{$sdiMode}</ModalitaPagamento>
              <DataScadenzaPagamento>{$dueStr}</DataScadenzaPagamento>
              <ImportoPagamento>{$importo}</ImportoPagamento>"
              // IBAN solo se bonifico
              .($sdiMode === 'MP05' && $iban
                ? "<IBAN>" . htmlspecialchars($iban, ENT_XML1, 'UTF-8') . "</IBAN>"
                : '')
            ."</DettaglioPagamento>";
        }

        $companyProvince = $company->legal_province !== null ? "<Provincia>" . substr($company->legal_province, 0, 2) . "</Provincia>" : "";
        $clientProvince = $client->province !== null ? "<Provincia>" . substr($client->province, 0, 2) . "</Provincia>" : "";

        // -------------------------
        // 9) ESCAPE DEI CAMPI XML
        // -------------------------
        $companyPiva = htmlspecialchars($company->piva, ENT_XML1, 'UTF-8');
        $companyStreet = htmlspecialchars($company->legal_street, ENT_XML1, 'UTF-8');
        $companyNumber = htmlspecialchars($company->legal_number, ENT_XML1, 'UTF-8');
        $companyZip = htmlspecialchars($company->legal_zip, ENT_XML1, 'UTF-8');
        $companyCity = htmlspecialchars($company->legal_city, ENT_XML1, 'UTF-8');
        $companyCountry = htmlspecialchars($company->legal_country, ENT_XML1, 'UTF-8');
        $companyPecEmail = htmlspecialchars($company->pec_email, ENT_XML1, 'UTF-8');
        
        $clientPiva = htmlspecialchars($client->piva, ENT_XML1, 'UTF-8');
        $clientName = htmlspecialchars(substr(trim($client->name), 0, 80), ENT_XML1, 'UTF-8');
        $clientAddress = htmlspecialchars($client->address, ENT_XML1, 'UTF-8');
        $clientCap = htmlspecialchars($client->cap, ENT_XML1, 'UTF-8');
        $clientCity = htmlspecialchars($client->city, ENT_XML1, 'UTF-8');
        $clientCountry = htmlspecialchars($client->country, ENT_XML1, 'UTF-8');
        
        $invoiceNumber = htmlspecialchars($num, ENT_XML1, 'UTF-8');
        $causaleEscaped = htmlspecialchars($causale, ENT_XML1, 'UTF-8');

        // -------------------------
        // 10) ASSEMBLAGGIO FINALE
        // -------------------------
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "\n";
        $xml .= '<p:FatturaElettronica versione="FPR12" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">';
        $xml .= "\n  <FatturaElettronicaHeader>";
        $xml .= "\n    <DatiTrasmissione>";
        $xml .= "\n      <IdTrasmittente>";
        $xml .= "\n        <IdPaese>IT</IdPaese>";
        $xml .= "\n        <IdCodice>{$companyPiva}</IdCodice>";
        $xml .= "\n      </IdTrasmittente>";
        // Use invoice number as ProgressivoInvio to ensure uniqueness
        $xml .= "\n      <ProgressivoInvio>{$invoice->invoice_number}</ProgressivoInvio>";
        $xml .= "\n      <FormatoTrasmissione>FPR12</FormatoTrasmissione>";
        $xml .= "\n      {$destinatario}";
        $xml .= "\n      <ContattiTrasmittente>";
        $xml .= "\n        <Email>{$companyPecEmail}</Email>";
        $xml .= "\n      </ContattiTrasmittente>";
        $xml .= "\n    </DatiTrasmissione>";
        $xml .= "\n    <CedentePrestatore>";
        $xml .= "\n      <DatiAnagrafici>";
        $xml .= "\n        <IdFiscaleIVA>";
        $xml .= "\n          <IdPaese>IT</IdPaese>";
        $xml .= "\n          <IdCodice>{$companyPiva}</IdCodice>";
        $xml .= "\n        </IdFiscaleIVA>";
        $xml .= "\n        {$cfXml}";
        $xml .= "\n        <Anagrafica>";
        $xml .= "\n          <Denominazione>{$companyName}</Denominazione>";
        $xml .= "\n        </Anagrafica>";
        $xml .= "\n        <RegimeFiscale>{$regime}</RegimeFiscale>";
        $xml .= "\n      </DatiAnagrafici>";
        $xml .= "\n      <Sede>";
        $xml .= "\n        <Indirizzo>{$companyStreet}</Indirizzo>";
        $xml .= "\n        <NumeroCivico>{$companyNumber}</NumeroCivico>";
        $xml .= "\n        <CAP>{$companyZip}</CAP>";
        $xml .= "\n        <Comune>{$companyCity}</Comune>";
        $xml .= "\n        {$companyProvince}";
        $xml .= "\n        <Nazione>{$companyCountry}</Nazione>";
        $xml .= "\n      </Sede>";
        $xml .= "\n      {$reaXml}";
        $xml .= "\n      {$contactsXml}";
        $xml .= "\n    </CedentePrestatore>";
        $xml .= "\n    <CessionarioCommittente>";
        $xml .= "\n      <DatiAnagrafici>";
        $xml .= "\n        <IdFiscaleIVA>";
        $xml .= "\n          <IdPaese>{$clientCountry}</IdPaese>";
        $xml .= "\n          <IdCodice>{$clientPiva}</IdCodice>";
        $xml .= "\n        </IdFiscaleIVA>";
        $xml .= "\n        <Anagrafica>";
        $xml .= "\n          <Denominazione>{$clientName}</Denominazione>";
        $xml .= "\n        </Anagrafica>";
        $xml .= "\n      </DatiAnagrafici>";
        $xml .= "\n      <Sede>";
        $xml .= "\n        <Indirizzo>{$clientAddress}</Indirizzo>";
        $xml .= "\n        <CAP>{$clientCap}</CAP>";
        $xml .= "\n        <Comune>{$clientCity}</Comune>";
        $xml .= "\n        {$clientProvince}";
        $xml .= "\n        <Nazione>{$clientCountry}</Nazione>";
        $xml .= "\n      </Sede>";
        $xml .= "\n    </CessionarioCommittente>";
        $xml .= "\n  </FatturaElettronicaHeader>";
        $xml .= "\n  <FatturaElettronicaBody>";
        $xml .= "\n    <DatiGenerali>";
        $xml .= "\n      <DatiGeneraliDocumento>";
        $xml .= "\n        <TipoDocumento>{$tipoDoc}</TipoDocumento>";
        $xml .= "\n        <Divisa>EUR</Divisa>";
        $xml .= "\n        <Data>{$dateDoc}</Data>";
        $xml .= "\n        <Numero>{$invoiceNumber}</Numero>";
        $xml .= "\n        {$scontoXml}";
        $xml .= "\n        <ImportoTotaleDocumento>{$totDoc}</ImportoTotaleDocumento>";
        $xml .= "\n        <Causale>{$causaleEscaped}</Causale>";
        $xml .= "\n        {$bollo}";
        $xml .= "\n        {$ddtXml}";
        $xml .= "\n        <AltriDatiGestionali>";
        $xml .= "\n          <TipoDato>AswSwHouse</TipoDato>";
        $xml .= "\n          <RiferimentoTesto>Newo.io</RiferimentoTesto>";
        $xml .= "\n        </AltriDatiGestionali>";
        $xml .= "\n      </DatiGeneraliDocumento>";
        $xml .= "\n      {$datiFattureCollegateXml}";
        $xml .= "\n    </DatiGenerali>";
        $xml .= "\n    <DatiBeniServizi>";
        $xml .= "\n      {$linee}";
        $xml .= "\n      {$riepilogoXml}";
        $xml .= "\n    </DatiBeniServizi>";
        $xml .= "\n    <DatiPagamento>";
        $xml .= "\n      {$datiPagamento}";
        $xml .= "\n    </DatiPagamento>";
        $xml .= "\n  </FatturaElettronicaBody>";
        $xml .= "\n</p:FatturaElettronica>";
        
        Log::info('Invoice XML generated', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $num,
            'xml' => $xml,
        ]);
        return $xml;
    }
}