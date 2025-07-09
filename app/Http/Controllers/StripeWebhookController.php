<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Stripe\Webhook;
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

        Log::info('Webhook ricevuto', ['request' => $request->all()]);

        return response()->json(['success' => true]);

        
        // $payload = $request->getContent();
        // $sigHeader = $request->header('Stripe-Signature');
        // $isTestBypass = $request->header('X-Test-Bypass') === 'true';

        // Bypass verifica firma per test
        // if ($isTestBypass) {
        //     Log::info('Test bypass enabled - skipping signature verification');
        //     $event = json_decode($payload);
        // } else {
        //     // Prova prima con il secret principale
        //     $endpointSecret = config('services.stripe.webhook_secret');
        //     $event = null;
        //     $error = null;

        //     try {
        //         $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        //     } catch (SignatureVerificationException $e) {
        //         $error = $e->getMessage();
                
        //         // Se fallisce, prova con i secret degli account connessi
        //         $stripeAccounts = StripeAccount::whereNotNull('webhook_secret')->get();
                
        //         foreach ($stripeAccounts as $stripeAccount) {
        //             try {
        //                 $event = Webhook::constructEvent($payload, $sigHeader, $stripeAccount->webhook_secret);
        //                 Log::info('Webhook verificato con account connesso', [
        //                     'account_id' => $stripeAccount->stripe_user_id
        //                 ]);
        //                 break;
        //             } catch (SignatureVerificationException $e2) {
        //                 continue;
        //             }
        //         }
                
        //         if (!$event) {
        //             Log::error('Webhook signature verification failed for all accounts', ['error' => $error]);
        //             return response()->json(['error' => 'Invalid signature'], 400);
        //         }
        //     }
        // }

        // Log::info('Webhook ricevuto', [
        //     'type' => $event->type,
        //     'id' => $event->id,
        //     'account' => $event->account ?? 'platform'
        // ]);

        // Gestisci i diversi tipi di eventi
        // switch ($event->type) {
        //     case 'customer.created':
        //     case 'customer.updated':
        //     case 'customer.deleted':
        //         $this->handleCustomerEvent($event->data->object, $event->account ?? null);
        //         break;

        //     case 'product.created':
        //     case 'product.updated':
        //     case 'product.deleted':
        //         $this->handleProductEvent($event->data->object, $event->account ?? null);
        //         break;

        //     case 'price.created':
        //     case 'price.updated':
        //     case 'price.deleted':
        //         $this->handlePriceEvent($event->data->object);
        //         break;

        //     case 'subscription.created':
        //     case 'subscription.updated':
        //     case 'subscription.deleted':
        //         $this->handleSubscriptionEvent($event->data->object, $event->account ?? null);
        //         break;

        //     case 'invoice.created':
        //     case 'invoice.updated':
        //     case 'invoice.payment_succeeded':
        //     case 'invoice.payment_failed':
        //         $this->handleInvoiceEvent($event->data->object, $event->account ?? null);
        //         break;

        //     case 'payment_intent.succeeded':
        //     case 'payment_intent.payment_failed':
        //     case 'charge.succeeded':
        //     case 'charge.failed':
        //         $this->handlePaymentEvent($event->data->object, $event->account ?? null);
        //         break;

        //     default:
        //         Log::info('Evento webhook ricevuto (non gestito specificamente)', [
        //             'type' => $event->type,
        //             'account_id' => $event->account ?? null,
        //             'object_id' => $event->data->object->id ?? null
        //         ]);
        // }

        // return response()->json(['success' => true]);
    }
} 