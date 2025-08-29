<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Stripe\StripeClient;
use App\Models\StripeAccount;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Price;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;


class StripeSyncCommand extends Command
{
    protected $signature = 'stripe:sync {stripe_account_id}';
    protected $description = 'Sincronizza clienti, prodotti, prezzi, abbonamenti e transazioni da Stripe per una company';

    public function handle()
    {
        $stripe_account_id = $this->argument('stripe_account_id');
        $stripeAccount = StripeAccount::where('id', $stripe_account_id)->first();

        if (! $stripeAccount) {
            $this->error('Stripe account non trovato per questa company.');
            return Command::FAILURE;
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $this->info('Inizio sincronizzazione Stripe');

        $this->syncClients($stripe, $stripeAccount);
        $this->syncProductsAndPrices($stripe, $stripeAccount);
        $this->syncSubscriptions($stripe, $stripeAccount);
       

        $this->info('Sincronizzazione completata! 🚀');

        return Command::SUCCESS;
    }

    protected function syncClients($stripe, $stripeAccount)
    {
        $this->info('Sync clienti...');
        $startingAfter = null;

        do {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }

            $customers = $stripe->customers->all($params, [
                'stripe_account' => $stripeAccount->stripe_user_id,
            ]);

            foreach ($customers->data as $customer) {

                $client = Client::updateOrCreate([
                    'stripe_customer_id' => $customer->id,
                ], [
                    'company_id' => $stripeAccount->company_id,
                    'stripe_account_id' => $stripeAccount->id,
                    'origin' => 'stripe',
                    'name' => $customer->name,
                ]);

                // Se non esiste un contatto per questo cliente, crealo
                if (! $client->contacts()->exists()) {
                    $client->contacts()->create([
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone ?? null,
                        'primary' => true,
                        'is_main_contact' => true,
                    ]);
                }
            }

            $startingAfter = count($customers->data) ? end($customers->data)->id : null;
        } while ($customers->has_more);
    }

    protected function syncProductsAndPrices($stripe, $stripeAccount)
    {
        $this->info('Sync prodotti e prezzi...');
        $products = $stripe->products->all(['limit' => 100], [
            'stripe_account' => $stripeAccount->stripe_user_id,
        ]);

        foreach ($products->data as $product) {
            $p = Product::updateOrCreate([
                'stripe_product_id' => $product->id,
            ], [
                'stripe_account_id' => $stripeAccount->id,
                'name' => $product->name,
                'active' => $product->active,
            ]);

            // Ora i prezzi per ogni prodotto
            $prices = $stripe->prices->all([
                'product' => $product->id,
                'limit' => 100,
            ], [
                'stripe_account' => $stripeAccount->stripe_user_id,
            ]);

            foreach ($prices->data as $price) {
                $priceModel = Price::updateOrCreate(
                    ['stripe_price_id' => $price->id],
                    [
                        'product_id'  => $p->id,
                        'unit_amount' => $price->unit_amount, // Mantieni in centesimi
                        'currency'    => $price->currency,
                        'interval'    => $price->recurring->interval ?? null,
                        'active'      => $price->active,
                    ]
                );
            
                // Metto in cache l’ID del price per 30 giorni
                Cache::put(
                    "stripe_price_id_map:{$price->id}",
                    $priceModel->id,
                    now()->addDays(30)
                );
            }
        }
    }

    protected function syncSubscriptions($stripe, $stripeAccount)
    {
        $this->info('Sync abbonamenti...');
        $startingAfter = null;

        do {
            $params = [
                'limit' => 100,
                'status' => 'all',
            ];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }

            $subscriptions = $stripe->subscriptions->all($params, [
                'stripe_account' => $stripeAccount->stripe_user_id,
            ]);

            foreach ($subscriptions->data as $subscription) {
                

                if ($subscription->status === 'incomplete_expired') {
                    continue; // ignora le scadute non completate
                }

                $client = Client::where('stripe_customer_id', $subscription->customer)->first();

                if (!$client) {
                    $this->warn("⚠️  Cliente non trovato per subscription {$subscription->id} (customer: {$subscription->customer}). Probabilmente eliminato - saltando subscription...");
                    continue; // Salta questa subscription
                }

                $subscriptionItem = $subscription->items->data[0];
                $price = $subscriptionItem->price;
                $quantity = $subscriptionItem->quantity ?? 1;
                $unitAmount = $price->unit_amount ?? 0; // Mantieni in centesimi
                $subtotal = $unitAmount * $quantity;

                $discountAmount = 0; // In centesimi
                $finalAmount = $subtotal; // In centesimi

                $this->info('Sync abbonamento: ' . $subscription->id);

                try {
                    if (! empty($subscription->latest_invoice)) {
                        // 1) Preleva tutte le fatture di questa subscription
                        $allInvoices = [];
                        $startingAfterInv = null;
                        do {
                            $paramsInv = [
                                'limit'        => 100,
                                'subscription' => $subscription->id,
                            ];
                            if ($startingAfterInv) {
                                $paramsInv['starting_after'] = $startingAfterInv;
                            }
                            $invoices = $stripe->invoices->all(
                                $paramsInv,
                                ['stripe_account' => $stripeAccount->stripe_user_id]
                            );
                            foreach ($invoices->data as $inv) {
                                $allInvoices[] = $inv;
                            }
                            $startingAfterInv = $invoices->has_more
                                ? end($invoices->data)->id
                                : null;
                        } while ($invoices->has_more);
                    
                        // 2) Somma di tutti i total_discount_amounts su ogni fattura (già in centesimi)
                        $discountAmount = 0;
                        foreach ($allInvoices as $inv) {
                            if (! empty($inv->total_discount_amounts)) {
                                foreach ($inv->total_discount_amounts as $d) {
                                    $discountAmount += $d['amount'] ?? 0;
                                }
                            }
                        }
                    
                        // 3) (Opzionale) se ti serve anche il totale fatturato (già in centesimi)
                        $finalAmount = 0;
                        foreach ($allInvoices as $inv) {
                            $finalAmount += $inv->total ?? 0;
                        }
                    
                        $this->info("Sync discounts for {$subscription->id}: {$discountAmount} centesimi");
                    }
                    
                    $finalAmount = $subtotal - $discountAmount; // Entrambi in centesimi
                
                // Ottieni IVA da Stripe (dalle fatture)
                $vatRate = 0.00;
                $vatAmount = 0;
                $totalWithVat = $finalAmount;
                
                if (!empty($subscription->latest_invoice)) {
                    // Prendi l'ultima fattura per ottenere i dati IVA
                    try {
                        $latestInvoice = $stripe->invoices->retrieve(
                            $subscription->latest_invoice,
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
                        $stripeFees = $this->calculateStripeFees($stripe, $subscription, $stripeAccount);
                        $finalAmountNet = max(0, $finalAmount - $stripeFees);
                        $totalWithVatNet = max(0, $totalWithVat - $stripeFees);
                        
                    } catch (\Exception $e) {
                        // Fallback: nessuna IVA
                        $this->warn("Errore nel recuperare IVA per {$subscription->id}: " . $e->getMessage());
                    }
                }
                    

                } catch (\Exception $e) {
                    // fallback: usa importo pieno
                    $finalAmount = $subtotal;
                    $vatRate = 0.00;
                    $vatAmount = 0;
                    $totalWithVat = $finalAmount;
                    
                    // Calcola commissioni anche nel fallback
                    $stripeFees = $this->calculateStripeFees($stripe, $subscription, $stripeAccount);
                    $finalAmountNet = max(0, $finalAmount - $stripeFees);
                    $totalWithVatNet = max(0, $totalWithVat - $stripeFees);
                }

                $priceId = Cache::remember(
                    "stripe_price_id_map:{$price->id}",
                    now()->addDays(30),
                    fn() => Price::where('stripe_price_id', $price->id)->value('id')
                );

                Subscription::updateOrCreate([
                    'stripe_subscription_id' => $subscription->id,
                ], [
                    'client_id'             => $client->id,
                    'price_id'              => $priceId,
                    'status'                => $subscription->status,
                    'company_id'            => $stripeAccount->company_id,
                    'start_date'            => Carbon::createFromTimestampUTC($subscription->start_date)->setTimezone('Europe/Rome'),
                    'current_period_start'  => isset($subscriptionItem->current_period_start)
                        ? Carbon::createFromTimestampUTC($subscriptionItem->current_period_start)
                        ->setTimezone('Europe/Rome')
                        : null,
                    'current_period_end'    => isset($subscriptionItem->current_period_end)
                        ? Carbon::createFromTimestampUTC($subscriptionItem->current_period_end)
                        ->setTimezone('Europe/Rome')
                        : null,
                    'stripe_account_id'     => $stripeAccount->id,
                    'quantity'              => $quantity,
                    'unit_amount'           => $unitAmount, // centesimi
                    'subtotal_amount'       => $subtotal, // centesimi
                    'discount_amount'       => $discountAmount, // centesimi
                    'final_amount'          => $finalAmount, // centesimi (senza IVA)
                    'vat_rate'              => $vatRate, // percentuale
                    'vat_amount'            => $vatAmount, // centesimi
                    'total_with_vat'        => $totalWithVat, // centesimi (con IVA)
                    'stripe_fees'           => $stripeFees, // centesimi
                    'final_amount_net'      => $finalAmountNet, // centesimi (netto)
                    'total_with_vat_net'    => $totalWithVatNet, // centesimi (netto con IVA)
                ]);
            }

            $startingAfter = count($subscriptions->data) ? end($subscriptions->data)->id : null;
        } while ($subscriptions->has_more);
    }

    /**
     * Calcola le commissioni Stripe totali per una subscription
     */
    protected function calculateStripeFees($stripe, $subscription, $stripeAccount)
    {
        try {
            // Per ora usiamo una stima semplice basata sull'importo
            // TODO: Implementare calcolo preciso quando avremo più dati
            
            $amount = $subscription->items->data[0]->price->unit_amount ?? 0;
            $quantity = $subscription->items->data[0]->quantity ?? 1;
            $totalAmount = $amount * $quantity;
            
            // Stima commissioni Stripe: 2.9% + €0.25
            $estimatedFees = round($totalAmount * 0.029) + 25;
            
            return max(0, $estimatedFees);
            
        } catch (\Exception $e) {
            // Fallback: stima le commissioni basata sull'importo totale
            $amount = $subscription->items->data[0]->price->unit_amount ?? 0;
            $quantity = $subscription->items->data[0]->quantity ?? 1;
            $totalAmount = $amount * $quantity;
            
            // Stima: 2.9% + €0.25 per transazione
            $estimatedFees = round($totalAmount * 0.029) + 25; // 2.9% + 25 centesimi
            return max(0, $estimatedFees);
        }
    }

    // protected function syncTransactions($stripe, $stripeAccount)
    // {
    //     $this->info('Sync transazioni...');
    //     $startingAfter = null;

    //     do {
    //         $params = ['limit' => 100];
    //         if ($startingAfter) {
    //             $params['starting_after'] = $startingAfter;
    //         }

    //         $transactions = $stripe->balanceTransactions->all($params, [
    //             'stripe_account' => $stripeAccount->stripe_user_id,
    //         ]);

    //         foreach ($transactions->data as $transaction) {
    //             Transaction::updateOrCreate([
    //                 'stripe_transaction_id' => $transaction->id,
    //             ], [
    //                 'company_id' => $stripeAccount->company_id,
    //                 'amount' => $transaction->amount,
    //                 'currency' => $transaction->currency,
    //                 'description' => $transaction->description,
    //                 'type' => $transaction->type,
    //             ]);
    //         }

    //         $startingAfter = count($transactions->data) ? end($transactions->data)->id : null;
    //     } while ($transactions->has_more);
    // }
        


}