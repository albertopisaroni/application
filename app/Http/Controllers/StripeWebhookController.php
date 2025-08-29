<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Stripe\Webhook;
use Stripe\StripeClient;
use Stripe\Exception\SignatureVerificationException;
use App\Models\StripeAccount;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Price;
use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;

class StripeWebhookController extends Controller
{
    /**
     * Gestisce i webhook di Stripe per aggiornamenti in tempo reale
     */
    public function handle(Request $request)
    {
        Log::info('Stripe webhook received', ['request' => $request->all()]);
        
        $payload = $request->getContent();
        $event = null;

        try {
            // Verifica la firma del webhook (opzionale ma raccomandato)
            if (config('services.stripe.webhook_secret')) {
                $sigHeader = $request->header('Stripe-Signature');
                $event = Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    config('services.stripe.webhook_secret')
                );
            } else {
                $event = json_decode($payload, true);
            }
        } catch (SignatureVerificationException $e) {
            Log::error('Webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Webhook parsing failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Trova lo Stripe Account basandosi sull'account ID del webhook
        $stripeAccount = null;
        if (isset($event['account'])) {
            $stripeAccount = StripeAccount::where('stripe_user_id', $event['account'])->first();
        }

        if (!$stripeAccount) {
            Log::warning('Stripe account not found for webhook', ['account' => $event['account'] ?? 'N/A']);
            return response()->json(['error' => 'Account not found'], 404);
        }

        try {
            $this->processWebhookEvent($event, $stripeAccount);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event_type' => $event['type'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Processa l'evento webhook in base al tipo
     */
    protected function processWebhookEvent($event, $stripeAccount)
    {
        $eventType = $event['type'];
        $eventData = $event['data']['object'];

        Log::info("Processing webhook event: {$eventType}", ['event_id' => $event['id']]);

        switch ($eventType) {
            case 'customer.created':
            case 'customer.updated':
                $this->handleCustomerEvent($eventData, $stripeAccount);
                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionEvent($eventData, $stripeAccount);
                break;

            case 'product.created':
            case 'product.updated':
                $this->handleProductEvent($eventData, $stripeAccount);
                break;

            case 'price.created':
            case 'price.updated':
                $this->handlePriceEvent($eventData, $stripeAccount);
                break;

            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($eventData, $stripeAccount);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($eventData, $stripeAccount);
                break;

            case 'customer.deleted':
                $this->handleCustomerDeleted($eventData, $stripeAccount);
                break;

            default:
                Log::info("Webhook event type not handled: {$eventType}");
                break;
        }
    }

    /**
     * Gestisce eventi customer (created/updated)
     */
    protected function handleCustomerEvent($customer, $stripeAccount)
    {
        Log::info("Handling customer event", ['customer_id' => $customer['id']]);

        $client = Client::updateOrCreate([
            'stripe_customer_id' => $customer['id'],
        ], [
            'company_id' => $stripeAccount->company_id,
            'stripe_account_id' => $stripeAccount->id,
            'origin' => 'stripe',
            'name' => $customer['name'],
        ]);

        // Aggiorna o crea il contatto principale
        if (!empty($customer['email']) || !empty($customer['name'])) {
            $contact = $client->contacts()->where('primary', true)->first();
            
            if ($contact) {
                $contact->update([
                    'name' => $customer['name'] ?? $contact->name,
                    'email' => $customer['email'] ?? $contact->email,
                    'phone' => $customer['phone'] ?? $contact->phone,
                ]);
            } else {
                $client->contacts()->create([
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'phone' => $customer['phone'] ?? null,
                    'primary' => true,
                    'is_main_contact' => true,
                ]);
            }
        }

        Log::info("Customer processed successfully", ['client_id' => $client->id]);
    }

    /**
     * Gestisce eventi subscription (created/updated)
     */
    protected function handleSubscriptionEvent($subscription, $stripeAccount)
    {
        Log::info("Handling subscription event", ['subscription_id' => $subscription['id']]);

        // Ignora subscription incomplete_expired
        if ($subscription['status'] === 'incomplete_expired') {
            Log::info("Ignoring incomplete_expired subscription", ['subscription_id' => $subscription['id']]);
            return;
        }

        $client = Client::where('stripe_customer_id', $subscription['customer'])->first();

        if (!$client) {
            Log::warning("Client not found for subscription", [
                'subscription_id' => $subscription['id'],
                'customer_id' => $subscription['customer']
            ]);
            return;
        }

        $subscriptionItem = $subscription['items']['data'][0];
        $price = $subscriptionItem['price'];
        $quantity = $subscriptionItem['quantity'] ?? 1;
        $unitAmount = $price['unit_amount'] ?? 0; // Mantieni in centesimi
        $subtotal = $unitAmount * $quantity;

        // Calcola discount se presente
        $discountAmount = $this->calculateSubscriptionDiscounts($subscription, $stripeAccount);
        $finalAmount = $subtotal - $discountAmount;
        
        // Ottieni IVA da Stripe (dalle fatture)
        $vatRate = 0.00;
        $vatAmount = 0;
        $totalWithVat = $finalAmount;
        
        if (!empty($subscription['latest_invoice'])) {
            // Prendi l'ultima fattura per ottenere i dati IVA
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $latestInvoice = $stripe->invoices->retrieve(
                    $subscription['latest_invoice'],
                    [],
                    ['stripe_account' => $stripeAccount->stripe_user_id]
                );
                
                // Usa i campi corretti di Stripe per l'IVA
                if (!empty($latestInvoice->total_taxes)) {
                    foreach ($latestInvoice->total_taxes as $tax) {
                        $vatAmount += $tax['amount'] ?? 0;
                        
                        // Recupera la percentuale dal tax_rate se disponibile
                        if (isset($tax['tax_rate_details']['tax_rate'])) {
                            try {
                                $taxRateId = $tax['tax_rate_details']['tax_rate'];
                                $taxRate = $stripe->taxRates->retrieve(
                                    $taxRateId,
                                    [],
                                    ['stripe_account' => $stripeAccount->stripe_user_id]
                                );
                                $vatRate = $taxRate->percentage ?? 0;
                            } catch (\Exception $taxE) {
                                // Fallback: calcola dalla proporzione
                                if ($latestInvoice->subtotal > 0) {
                                    $vatRate = round(($vatAmount / $latestInvoice->subtotal) * 100, 2);
                                }
                            }
                        }
                    }
                }
                
                // Fallback: usa la differenza total - subtotal
                if ($vatAmount == 0 && $latestInvoice->total && $latestInvoice->subtotal) {
                    $vatAmount = $latestInvoice->total - $latestInvoice->subtotal;
                    if ($latestInvoice->subtotal > 0 && $vatAmount > 0) {
                        $vatRate = round(($vatAmount / $latestInvoice->subtotal) * 100, 2);
                    }
                }
                
                // Calcola total with VAT solo se ha senso
                if ($vatRate > 0 && $vatAmount > 0) {
                    $totalWithVat = $finalAmount + $vatAmount;
                } else {
                    // Nessuna IVA o valori inconsistenti (inclusi valori negativi)
                    $vatRate = 0.00;
                    $vatAmount = 0;
                    $totalWithVat = $finalAmount;
                }
                
                // Assicurati che vat_amount non sia mai negativo (unsignedInteger)
                if ($vatAmount < 0) {
                    $vatAmount = 0;
                    $vatRate = 0.00;
                    $totalWithVat = $finalAmount;
                }
                
                // Calcola commissioni Stripe per questa subscription
                $stripeFees = $this->calculateStripeFees($subscription, $stripeAccount);
                $finalAmountNet = max(0, $finalAmount - $stripeFees);
                $totalWithVatNet = max(0, $totalWithVat - $stripeFees);
                
            } catch (\Exception $e) {
                // Fallback: nessuna IVA
                $vatRate = 0.00;
                $vatAmount = 0;
                $totalWithVat = $finalAmount;
                
                // Calcola commissioni anche nel fallback
                $stripeFees = $this->calculateStripeFees($subscription, $stripeAccount);
                $finalAmountNet = max(0, $finalAmount - $stripeFees);
                $totalWithVatNet = max(0, $totalWithVat - $stripeFees);
                
                Log::warning("Errore nel recuperare IVA per subscription", [
                    'subscription_id' => $subscription['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Trova il price_id locale
        $priceId = Cache::remember(
            "stripe_price_id_map:{$price['id']}",
            now()->addDays(30),
            fn() => Price::where('stripe_price_id', $price['id'])->value('id')
        );

        Subscription::updateOrCreate([
            'stripe_subscription_id' => $subscription['id'],
        ], [
            'client_id' => $client->id,
            'price_id' => $priceId,
            'status' => $subscription['status'],
            'company_id' => $stripeAccount->company_id,
            'start_date' => Carbon::createFromTimestampUTC($subscription['start_date'])->setTimezone('Europe/Rome'),
            'current_period_start' => isset($subscription['current_period_start'])
                ? Carbon::createFromTimestampUTC($subscription['current_period_start'])->setTimezone('Europe/Rome')
                : null,
            'current_period_end' => isset($subscriptionItem['current_period_end'])
                ? Carbon::createFromTimestampUTC($subscriptionItem['current_period_end'])->setTimezone('Europe/Rome')
                : null,
            'stripe_account_id' => $stripeAccount->id,
            'quantity' => $quantity,
            'unit_amount' => $unitAmount, // centesimi
            'subtotal_amount' => $subtotal, // centesimi
            'discount_amount' => $discountAmount, // centesimi
            'final_amount' => $finalAmount, // centesimi (senza IVA)
            'vat_rate' => $vatRate, // percentuale
            'vat_amount' => $vatAmount, // centesimi
            'total_with_vat' => $totalWithVat, // centesimi (con IVA)
            'stripe_fees' => $stripeFees, // centesimi
            'final_amount_net' => $finalAmountNet, // centesimi (netto)
            'total_with_vat_net' => $totalWithVatNet, // centesimi (netto con IVA)
        ]);

        Log::info("Subscription processed successfully", ['subscription_id' => $subscription['id']]);
    }

    /**
     * Calcola i discount di una subscription
     */
    protected function calculateSubscriptionDiscounts($subscription, $stripeAccount)
    {
        try {
            if (empty($subscription['latest_invoice'])) {
                return 0;
            }

            $stripe = new StripeClient(config('services.stripe.secret'));
            
            // Preleva tutte le fatture di questa subscription
            $allInvoices = [];
            $startingAfter = null;
            do {
                $params = [
                    'limit' => 100,
                    'subscription' => $subscription['id'],
                ];
                if ($startingAfter) {
                    $params['starting_after'] = $startingAfter;
                }
                $invoices = $stripe->invoices->all(
                    $params,
                    ['stripe_account' => $stripeAccount->stripe_user_id]
                );
                foreach ($invoices->data as $inv) {
                    $allInvoices[] = $inv;
                }
                $startingAfter = $invoices->has_more ? end($invoices->data)->id : null;
            } while ($invoices->has_more);

            // Somma tutti i discount
            $discountAmount = 0;
            foreach ($allInvoices as $inv) {
                if (!empty($inv->total_discount_amounts)) {
                    foreach ($inv->total_discount_amounts as $d) {
                        $discountAmount += $d['amount'] ?? 0;
                    }
                }
            }

            return $discountAmount; // Mantieni in centesimi
        } catch (\Exception $e) {
            Log::error("Error calculating subscription discounts", [
                'subscription_id' => $subscription['id'],
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Gestisce eventi product (created/updated)
     */
    protected function handleProductEvent($product, $stripeAccount)
    {
        Log::info("Handling product event", ['product_id' => $product['id']]);

        Product::updateOrCreate([
            'stripe_product_id' => $product['id'],
        ], [
            'stripe_account_id' => $stripeAccount->id,
            'name' => $product['name'],
            'active' => $product['active'],
        ]);

        Log::info("Product processed successfully", ['product_id' => $product['id']]);
    }

    /**
     * Gestisce eventi price (created/updated)
     */
    protected function handlePriceEvent($price, $stripeAccount)
    {
        Log::info("Handling price event", ['price_id' => $price['id']]);

        // Trova il prodotto locale
        $product = Product::where('stripe_product_id', $price['product'])->first();
        
        if (!$product) {
            Log::warning("Product not found for price", [
                'price_id' => $price['id'],
                'product_id' => $price['product']
            ]);
            return;
        }

        $priceModel = Price::updateOrCreate(
            ['stripe_price_id' => $price['id']],
            [
                'product_id' => $product->id,
                'unit_amount' => $price['unit_amount'] ?? 0, // Mantieni in centesimi
                'currency' => $price['currency'],
                'interval' => $price['recurring']['interval'] ?? null,
                'active' => $price['active'],
            ]
        );

        // Aggiorna cache
        Cache::put(
            "stripe_price_id_map:{$price['id']}",
            $priceModel->id,
            now()->addDays(30)
        );

        Log::info("Price processed successfully", ['price_id' => $price['id']]);
    }

    /**
     * Gestisce checkout.session.completed
     */
    protected function handleCheckoutSessionCompleted($session, $stripeAccount)
    {
        Log::info("Handling checkout session completed", ['session_id' => $session['id']]);

        // Se c'è una subscription associata, trigghera l'aggiornamento
        if (!empty($session['subscription'])) {
            try {
                $stripe = new StripeClient(config('services.stripe.secret'));
                $subscription = $stripe->subscriptions->retrieve(
                    $session['subscription'],
                    [],
                    ['stripe_account' => $stripeAccount->stripe_user_id]
                );
                $this->handleSubscriptionEvent($subscription, $stripeAccount);
            } catch (\Exception $e) {
                Log::error("Error retrieving subscription from checkout session", [
                    'session_id' => $session['id'],
                    'subscription_id' => $session['subscription'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Gestisce customer.subscription.deleted
     */
    protected function handleSubscriptionDeleted($subscription, $stripeAccount)
    {
        Log::info("Handling subscription deleted", ['subscription_id' => $subscription['id']]);

        $localSubscription = Subscription::where('stripe_subscription_id', $subscription['id'])->first();
        
        if ($localSubscription) {
            $localSubscription->update(['status' => 'canceled']);
            Log::info("Subscription marked as canceled", ['subscription_id' => $subscription['id']]);
        }
    }

    /**
     * Gestisce customer.deleted
     */
    protected function handleCustomerDeleted($customer, $stripeAccount)
    {
        Log::info("Handling customer deleted", ['customer_id' => $customer['id']]);

        $client = Client::where('stripe_customer_id', $customer['id'])->first();
        
        if ($client) {
            // Soft delete o marca come inactive invece di cancellare completamente
            // $client->delete(); // Se vuoi hard delete
            $client->update(['active' => false]); // Se preferisci soft deactivation
            Log::info("Client deactivated", ['client_id' => $client->id]);
        }
    }

    /**
     * Calcola le commissioni Stripe per una subscription
     */
    protected function calculateStripeFees($subscription, $stripeAccount)
    {
        try {
            // Usa la stessa logica del StripeSyncCommand: stima semplice
            $amount = $subscription['items']['data'][0]['price']['unit_amount'] ?? 0;
            $quantity = $subscription['items']['data'][0]['quantity'] ?? 1;
            $totalAmount = $amount * $quantity;
            
            // Stima commissioni Stripe: 2.9% + €0.25
            $estimatedFees = round($totalAmount * 0.029) + 25;
            
            return max(0, $estimatedFees);
            
        } catch (\Exception $e) {
            // Fallback: commissioni minime
            return 25; // Solo la fee fissa di €0.25
        }
    }
} 