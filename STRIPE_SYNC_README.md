# Stripe Sync & Webhook System

## Panoramica

Questo sistema permette di sincronizzare i dati Stripe e mantenerli aggiornati tramite webhook in tempo reale. È progettato per gestire migliaia di account connessi in modo efficiente.

## Strategia di Sincronizzazione

### 1. Sincronizzazione Iniziale
Usa il comando `stripe:sync` con il flag `--initial` per la prima sincronizzazione completa:

```bash
# Singolo account
php artisan stripe:sync {stripe_account_id} --initial

# Batch di account (per sincronizzazione iniziale massiva)
php artisan stripe:batch-sync --limit=50 --company_id=123
```

### 2. Aggiornamenti in Tempo Reale
Dopo la sincronizzazione iniziale, i webhook di Stripe mantengono i dati aggiornati automaticamente.

## Comandi Disponibili

### `stripe:sync`
Sincronizza un singolo account Stripe.

```bash
php artisan stripe:sync {stripe_account_id} [--initial]
```

**Parametri:**
- `stripe_account_id`: ID dell'account Stripe da sincronizzare
- `--initial`: Flag per sincronizzazione iniziale più dettagliata

### `stripe:batch-sync`
Sincronizza più account in batch (per sincronizzazione iniziale).

```bash
php artisan stripe:batch-sync [--limit=10] [--company_id=]
```

**Parametri:**
- `--limit`: Numero massimo di account da sincronizzare (default: 10)
- `--company_id`: ID specifico della company da sincronizzare (opzionale)

### `stripe:test-webhook`
Testa il processing di un webhook usando un payload JSON.

```bash
php artisan stripe:test-webhook --file=webhook-test-customer-created.json
```

## Configurazione Webhook

### 1. Variabili di Ambiente
Aggiungi al tuo `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

### 2. URL Webhook
Configura questo URL nel tuo dashboard Stripe:
```
https://your-domain.com/api/stripe/webhook
```

### 3. Eventi da Ascoltare
Il sistema gestisce automaticamente questi eventi:

- `customer.created`
- `customer.updated` 
- `customer.deleted`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `product.created`
- `product.updated`
- `price.created`
- `price.updated`
- `checkout.session.completed`

## Flusso di Lavoro Consigliato

### Setup Iniziale
1. **Prima sincronizzazione**: Usa `stripe:batch-sync` per sincronizzare tutti gli account esistenti
2. **Configura webhook**: Imposta l'endpoint webhook nel dashboard Stripe
3. **Test webhook**: Usa `stripe:test-webhook` per verificare il funzionamento

### Manutenzione
- I webhook mantengono i dati sincronizzati automaticamente
- Usa `stripe:sync` (senza `--initial`) solo per recuperare eventuali dati mancanti
- Monitora i log per eventuali errori nei webhook

## Sicurezza

### Verifica Firma Webhook
Il sistema verifica automaticamente la firma dei webhook Stripe se `STRIPE_WEBHOOK_SECRET` è configurato.

### Gestione Errori
- Tutti gli errori vengono loggati
- I webhook falliti ritornano codici HTTP appropriati per il retry automatico di Stripe
- Account non trovati vengono gestiti gracefully

## Monitoring

### Log
Tutti gli eventi vengono loggati con dettagli utili:
- Tipo di evento webhook
- ID dell'account Stripe
- Successi e fallimenti
- Dettagli degli errori

### Metriche
Monitora questi aspetti:
- Frequenza dei webhook ricevuti
- Tempo di processing dei webhook
- Rate di errori nei webhook
- Stato delle sincronizzazioni

## Troubleshooting

### Webhook non funzionano
1. Verifica che `STRIPE_WEBHOOK_SECRET` sia configurato correttamente
2. Controlla che l'URL webhook sia raggiungibile
3. Verifica i log per errori di signature verification

### Dati mancanti
1. Esegui una sincronizzazione manuale: `php artisan stripe:sync {account_id}`
2. Verifica che l'account Stripe sia presente nel database
3. Controlla i log per eventuali errori durante il processing

### Performance
- I webhook sono progettati per essere veloci (< 1 secondo)
- Per sincronizzazioni massive, usa `stripe:batch-sync` con limit appropriato
- La cache viene usata per ottimizzare le query ripetitive

## File di Test

È incluso un file di esempio `webhook-test-customer-created.json` per testare il sistema:

```bash
php artisan stripe:test-webhook --file=webhook-test-customer-created.json
```

Puoi creare file simili per testare altri tipi di eventi webhook.
