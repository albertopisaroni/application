@php
    // $invoice → oggetto Invoice passato al mailable
    // $url     → link temporaneo o S3 al PDF (= secondo argomento del mailable)
@endphp

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Fattura {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif">
    <h1>Fattura {{ $invoice->invoice_number }}</h1>

    <p>Ciao {{ $invoice->client->name }},</p>

    <p>in allegato trovi la tua fattura del
       {{ $invoice->issue_date->format('d/m/Y') }}
       per l’importo di <strong>€ {{ number_format($invoice->total, 2, ',', '.') }}</strong>.</p>

    <p>
        <a href="{{ $url }}">Scarica il PDF</a>
    </p>

    <p>Grazie e a presto!</p>

    <hr>
    <small>{{ $invoice->company->name }} – P.IVA {{ $invoice->company->piva }}</small>
</body>
</html>