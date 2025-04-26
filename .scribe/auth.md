# Autenticazione delle richieste

Per autenticare le richieste, includi un'intestazione **`Authorization`** con valore **`"Bearer {YOUR_API_TOKEN}"`**.

Tutti gli endpoint che richiedono autenticazione sono contrassegnati con il badge `richiede autenticazione` nella documentazione sottostante.

Puoi creare e gestire i tuoi token di autenticazione accedendo alla tua area personale su <a href="{{ config('app.app_url') }}/company" target="_blank">{{ config('app.app_url') }}/company</a>.<br><br>
Ogni richiesta alle API deve includere un'intestazione:

<pre><code>Authorization: Bearer {YOUR_API_TOKEN}</code></pre>

I token sono legati alla tua azienda e permettono di accedere alle relative risorse.
