<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Http\Request;

class TestStripeWebhookCommand extends Command
{
    protected $signature = 'stripe:test-webhook {--file= : File JSON con il payload del webhook da testare}';
    protected $description = 'Testa il processing di un webhook Stripe usando un payload JSON';

    public function handle()
    {
        $file = $this->option('file');
        
        if (!$file) {
            $this->error('Specifica un file JSON con --file=path/to/webhook.json');
            return Command::FAILURE;
        }

        if (!file_exists($file)) {
            $this->error("File non trovato: {$file}");
            return Command::FAILURE;
        }

        $payload = file_get_contents($file);
        $data = json_decode($payload, true);

        if (!$data) {
            $this->error('File JSON non valido');
            return Command::FAILURE;
        }

        $this->info("Testing webhook event: {$data['type']}");
        $this->info("Event ID: {$data['id']}");

        // Crea una fake request
        $request = Request::create('/api/stripe/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        
        // Temporaneamente rimuovi il webhook secret per il test
        $originalSecret = config('services.stripe.webhook_secret');
        config(['services.stripe.webhook_secret' => null]);

        // Processa il webhook
        $controller = new StripeWebhookController();
        
        try {
            $response = $controller->handle($request);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();

            if ($statusCode === 200) {
                $this->info('✅ Webhook processato con successo!');
                $this->info("Response: {$content}");
            } else {
                $this->error("❌ Errore nel processing del webhook (Status: {$statusCode})");
                $this->error("Response: {$content}");
            }

            return $statusCode === 200 ? Command::SUCCESS : Command::FAILURE;
        } catch (\Exception $e) {
            $this->error("❌ Eccezione durante il processing:");
            $this->error($e->getMessage());
            return Command::FAILURE;
        } finally {
            // Ripristina il webhook secret originale
            config(['services.stripe.webhook_secret' => $originalSecret]);
        }
    }
}
