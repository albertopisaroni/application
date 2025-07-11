<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentMethod;
use Illuminate\Support\Carbon;

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
        $companyName = htmlspecialchars(trim($company->legal_name), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $isForf  = (bool) $company->forfettario;

        $codiceFiscale = trim($company->codice_fiscale);
        $cfXml = ($codiceFiscale !== '' && $codiceFiscale !== $company->piva)
            ? "<CodiceFiscale>{$codiceFiscale}</CodiceFiscale>"
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
            ? "<CodiceDestinatario>{$sdi}</CodiceDestinatario>"
            : "<CodiceDestinatario>0000000</CodiceDestinatario>"
              . ($pec ? "<PECDestinatario>{$pec}</PECDestinatario>" : '');

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
              <Ufficio>{$company->rea_ufficio}</Ufficio>
              <NumeroREA>{$company->rea_numero}</NumeroREA>"
              . (! empty($company->rea_stato_liquidazione)
                  ? "<StatoLiquidazione>{$company->rea_stato_liquidazione}</StatoLiquidazione>"
                  : '')
            ."</IscrizioneREA>";
        }

        $contactsXml = '';
        if (! empty($company->email)) {
            $contactsXml = "
            <Contatti>
              <Email>{$company->email}</Email>
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
            ));

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
              <NumeroDDT>{$invoice->ddt_number}</NumeroDDT>
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
              <IdDocumento>{$originalInvoice->invoice_number}</IdDocumento>
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
            if ($due->lt($invoice->issue_date)) {
                throw new \Exception(
                    "Scadenza {$due->toDateString()} deve essere successiva alla data fattura {$invoice->issue_date->toDateString()}."
                );
            }
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
                ? "<IBAN>{$iban}</IBAN>"
                : '')
            ."</DettaglioPagamento>";
        }

        // -------------------------
        // 9) ASSEMBLAGGIO FINALE
        // -------------------------
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<p:FatturaElettronica versione="FPR12"
  xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
  xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 
                      http://www.fatturapa.gov.it/export/fatturazione/sdi/fatturapa/v1.2/Schema_del_file_xml_FatturaPA_versione_1.2.xsd">
  <FatturaElettronicaHeader>
    <DatiTrasmissione>
      <IdTrasmittente>
        <IdPaese>IT</IdPaese>
        <IdCodice>{$company->piva}</IdCodice>
      </IdTrasmittente>
      <ProgressivoInvio>{$invoice->sdi_attempt}</ProgressivoInvio>
      <FormatoTrasmissione>FPR12</FormatoTrasmissione>
      {$destinatario}
      <ContattiTrasmittente>
        <Email>{$company->pec_email}</Email>
      </ContattiTrasmittente>
    </DatiTrasmissione>
    <CedentePrestatore>
      <DatiAnagrafici>
        <IdFiscaleIVA>
          <IdPaese>IT</IdPaese>
          <IdCodice>{$company->piva}</IdCodice>
        </IdFiscaleIVA>
        {$cfXml}
        <Anagrafica>
          <Denominazione>{$companyName}</Denominazione>
        </Anagrafica>
        <RegimeFiscale>{$regime}</RegimeFiscale>
      </DatiAnagrafici>
      <Sede>
        <Indirizzo>{$company->legal_street}</Indirizzo>
        <NumeroCivico>{$company->legal_number}</NumeroCivico>
        <CAP>{$company->legal_zip}</CAP>
        <Comune>{$company->legal_city}</Comune>
        <Provincia>{$company->legal_province}</Provincia>
        <Nazione>{$company->legal_country}</Nazione>
      </Sede>
      {$reaXml}
      {$contactsXml}
    </CedentePrestatore>
    <CessionarioCommittente>
      <DatiAnagrafici>
        <IdFiscaleIVA>
          <IdPaese>IT</IdPaese>
          <IdCodice>{$client->piva}</IdCodice>
        </IdFiscaleIVA>
        <Anagrafica>
          <Denominazione>{$client->name}</Denominazione>
        </Anagrafica>
      </DatiAnagrafici>
      <Sede>
        <Indirizzo>{$client->address}</Indirizzo>
        <CAP>{$client->cap}</CAP>
        <Comune>{$client->city}</Comune>
        <Provincia>{$client->province}</Provincia>
        <Nazione>{$client->country}</Nazione>
      </Sede>
    </CessionarioCommittente>
  </FatturaElettronicaHeader>
  <FatturaElettronicaBody>
    <DatiGenerali>
      <DatiGeneraliDocumento>
        <TipoDocumento>{$tipoDoc}</TipoDocumento>
        <Divisa>EUR</Divisa>
        <Data>{$dateDoc}</Data>
        <Numero>{$num}</Numero>
        {$scontoXml}
        <ImportoTotaleDocumento>{$totDoc}</ImportoTotaleDocumento>
        <Causale>{$causale}</Causale>
        {$bollo}
        {$ddtXml}
        <AltriDatiGestionali>
          <TipoDato>AswSwHouse</TipoDato>
          <RiferimentoTesto>Newo.io</RiferimentoTesto>
        </AltriDatiGestionali>
      </DatiGeneraliDocumento>
      {$datiFattureCollegateXml}
    </DatiGenerali>
    <DatiBeniServizi>
      {$linee}
      {$riepilogoXml}
    </DatiBeniServizi>
    <DatiPagamento>
      {$datiPagamento}
    </DatiPagamento>
  </FatturaElettronicaBody>
</p:FatturaElettronica>
XML;
    }
}