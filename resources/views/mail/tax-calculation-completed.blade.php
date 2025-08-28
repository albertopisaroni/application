<x-mail::message>
# Calcolo Tasse Completato

Gentile {{ $company->name }},

Il calcolo delle tasse per l'anno {{ $year }} è stato completato con successo.

## Riepilogo Importi

<x-mail::table>
| Tipo | Importo |
|:-----|--------:|
| Imposta Sostitutiva | € {{ number_format($summary['totale_imposta'], 2, ',', '.') }} |
| Contributi INPS | € {{ number_format($summary['totale_inps'], 2, ',', '.') }} |
| **TOTALE DA VERSARE** | **€ {{ number_format($summary['totale_da_versare'], 2, ',', '.') }}** |
@if($summary['crediti'] > 0)
| Crediti generati | € {{ number_format($summary['crediti'], 2, ',', '.') }} |
@endif
</x-mail::table>

## Scadenze Pagamenti

@foreach($summary['scadenze'] as $scadenza => $pagamenti)
### Scadenza: {{ $scadenza }}

<x-mail::table>
| Descrizione | Importo |
|:------------|--------:|
@foreach($pagamenti as $pagamento)
| {{ $pagamento['descrizione'] }} | € {{ number_format($pagamento['importo'], 2, ',', '.') }} |
@endforeach
</x-mail::table>

@endforeach

## Dettaglio Bollettini

<x-mail::table>
| Tipo | Descrizione | Importo | Scadenza |
|:-----|:------------|--------:|---------:|
@foreach($taxRecords as $record)
@if($record['payment_status'] !== 'CREDIT')
| {{ str_replace('_', ' ', $record['tax_type']) }} | {{ $record['description'] }} | € {{ number_format($record['amount'], 2, ',', '.') }} | {{ $record['due_date']->format('d/m/Y') }} |
@endif
@endforeach
</x-mail::table>

@if(collect($taxRecords)->where('payment_status', 'CREDIT')->count() > 0)
## Crediti Disponibili

<x-mail::table>
| Descrizione | Importo |
|:------------|--------:|
@foreach($taxRecords as $record)
@if($record['payment_status'] === 'CREDIT')
| {{ $record['description'] }} | € {{ number_format($record['amount'], 2, ',', '.') }} |
@endif
@endforeach
</x-mail::table>

**⚠️ IMPORTANTE:** I crediti sono utilizzabili in F24 solo **dopo la presentazione della dichiarazione** dei redditi. Non è possibile utilizzarli "a piacere" prima della dichiarazione.
@endif

## Note Importanti

- I pagamenti devono essere effettuati entro le scadenze indicate
- Utilizzare i codici tributo corretti per il modello F24
- In caso di pagamento dopo il 30 giugno, applicare la maggiorazione dello 0,40%
- Conservare le ricevute di pagamento per eventuali controlli

<x-mail::button :url="config('app.url') . '/taxes'">
Visualizza Bollettini
</x-mail::button>

Per qualsiasi domanda o chiarimento, non esitate a contattarci.

Cordiali saluti,<br>
{{ config('app.name') }}
</x-mail::message>