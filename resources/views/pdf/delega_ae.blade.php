<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Delega Agenzia Entrate</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.6; }
    </style>
</head>
<body>
    <h2 style="text-align: center;">DELEGA PER ACCESSO AI SERVIZI TELEMATICI DELL’AGENZIA DELLE ENTRATE</h2>

    <p>Io sottoscritto <strong>{{ $delegante_nome }}</strong>, codice fiscale <strong>{{ $delegante_cf }}</strong>,
    titolare della Partita IVA <strong>{{ $delegante_piva }}</strong>,</p>

    <p>DELEGO il Sig. <strong>{{ $delegato_nome }}</strong> (CF: <strong>{{ $delegato_cf }}</strong>) della società <strong>{{ $delegato_societa }}</strong> (P.IVA: {{ $delegato_piva }}),</p>

    <p>ad accedere per mio conto ai servizi telematici dell’Agenzia delle Entrate, con particolare riferimento a:</p>

    <ul>
        <li>Accesso al Cassetto Fiscale</li>
        <li>Consultazione e scaricamento delle Fatture Elettroniche</li>
        <li>Registrazione o modifica dell’indirizzo telematico per la ricezione delle fatture elettroniche (Codice SDI)</li>
    </ul>

    <p>La presente delega ha validità dalla data odierna e fino a revoca scritta, e comunque per un periodo massimo di 2 anni.</p>

    <br><br>

    <p>Data: {{ $data_delega }}</p>
    <p>Firma del delegante:</p>
    <p style="margin-top: 50px;">_____________________________</p>
</body>
</html>