<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\StripeClient;
use App\Models\StripeAccount;
use App\Models\Client;
use App\Models\Product;
use App\Models\Price;
use App\Models\Subscription;
use App\Models\Transaction;

class StripeSyncCommand extends Command
{
    protected $signature = 'stripe:sync {company_id}';
    protected $description = 'Sincronizza clienti, prodotti, prezzi, abbonamenti e transazioni da Stripe per una company';

    public function handle()
    {
        $companyId = $this->argument('company_id');
        $stripeAccount = StripeAccount::where('company_id', $companyId)->first();

        if (! $stripeAccount) {
            $this->error('Stripe account non trovato per questa company.');
            return Command::FAILURE;
        }

        $stripe = new StripeClient(config('services.stripe.secret'));

        $this->info('Inizio sincronizzazione Stripe per company_id=' . $companyId);

        // $this->syncClients($stripe, $stripeAccount);
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
                Client::updateOrCreate([
                    'stripe_customer_id' => $customer->id,
                ], [
                    'company_id' => $stripeAccount->company_id,
                    'stripe_account_id' => $stripeAccount->id,
                    'origin' => 'stripe',
                    'email' => $customer->email,
                    'name' => $customer->name,
                ]);
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
                Price::updateOrCreate([
                    'stripe_price_id' => $price->id,
                ], [
                    'product_id' => $p->id,
                    'unit_amount' => $price->unit_amount,
                    'currency' => $price->currency,
                    'interval' => $price->recurring->interval ?? null,
                    'active' => $price->active,
                ]);
            }
        }
    }

    protected function syncSubscriptions($stripe, $stripeAccount)
    {
        $this->info('Sync abbonamenti...');
        $startingAfter = null;

        do {
            $params = ['limit' => 100];
            if ($startingAfter) {
                $params['starting_after'] = $startingAfter;
            }

            $subscriptions = $stripe->subscriptions->all($params, [
                'stripe_account' => $stripeAccount->stripe_user_id,
            ]);

            foreach ($subscriptions->data as $subscription) {
                $client = Client::where('stripe_customer_id', $subscription->customer)->first();

                if (! $client) {
                    continue; // Cliente non trovato
                }

                Subscription::updateOrCreate([
                    'stripe_subscription_id' => $subscription->id,
                ], [
                    'client_id' => $client->id,
                    'price_id' => Price::where('stripe_price_id', $subscription->items->data[0]->price->id ?? null)->value('id'),
                    'status' => $subscription->status,
                    'start_date' => date('Y-m-d H:i:s', $subscription->start_date),
                    'current_period_end' => isset($subscription->current_period_end)
                        ? date('Y-m-d H:i:s', $subscription->current_period_end)
                        : null,
                ]);
            }

            // ora ok

            $startingAfter = count($subscriptions->data) ? end($subscriptions->data)->id : null;
        } while ($subscriptions->has_more);
    }
        


}