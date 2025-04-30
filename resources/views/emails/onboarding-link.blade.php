@php
    $link = $link ?? '#';
@endphp

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Benvenuto su Newo</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f9f9fb; font-family: Arial, sans-serif;">
    <div style="max-width: 600px; margin: 0 auto; padding: 40px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">

        {{-- Logo --}}
        <div style="text-align: center; margin-bottom: 32px;">
            <img src="https://via.placeholder.com/150x40?text=Newo" alt="Newo Logo" style="height: 40px;">
        </div>

        {{-- Titolo --}}
        <h2 style="font-size: 22px; color: #111; margin-bottom: 20px;">Hai ricevuto un invito!</h2>

        {{-- Testo --}}
        <p style="font-size: 16px; color: #333; line-height: 1.6;">
            Ciao,<br>
            Hai ricevuto un invito per accedere alla piattaforma Newo.<br>
            Clicca sul pulsante qui sotto per iniziare subito il tuo onboarding.
        </p>

        {{-- Pulsanti --}}
        <div style="margin-top: 32px; display: flex; gap: 16px;">
            <a href="{{ $link }}"
               style="text-decoration: none; background-color: #a78bfa; color: white; padding: 14px 24px; border-radius: 8px; font-weight: bold;">
                Inizia l'onboarding
            </a>
        </div>

        {{-- Link fallback --}}
        <p style="font-size: 14px; color: #777; margin-top: 32px;">
            Oppure copia e incolla questo link nel tuo browser:<br>
            <a href="{{ $link }}" style="color: #6366f1;">{{ $link }}</a>
        </p>

        {{-- Footer --}}
        <p style="font-size: 12px; color: #999; margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px;">
            Newo ti ha inviato questa email per conto di un cliente che ti ha invitato sulla piattaforma.
            Se vuoi sapere come gestiamo i dati o leggere la nostra privacy policy, <a href="#" style="color: #6366f1;">clicca qui</a>.<br>
            📍 Newo Srl – Via del Lavoro 22, Milano
        </p>
    </div>
</body>
</html>