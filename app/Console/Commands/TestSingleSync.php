<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Models\StripeAccount;
use Stripe\StripeClient;
use Carbon\Carbon;

class TestSingleSync extends Command
{
    protected $signature = 'test:single-sync {subscription_id}';
    protected $description = 'Test sync for a single subscription';

    public function handle()
    {
        $subId = $this->argument('subscription_id');
        $sub = Subscription::where('stripe_subscription_id', $subId)->first();
        
        if (!$sub) {
            $this->error('Subscription not found');
            return;
        }

        $stripe = new StripeClient(config('services.stripe.secret'));
        $stripeAccount = $sub->stripeAccount;
        
        $this->info("=== TESTING FEES FOR {$subId} ===");
        
        $subscription = $stripe->subscriptions->retrieve(
            $subId, 
            [], 
            ['stripe_account' => $stripeAccount->stripe_user_id]
        );
        
        $fees = $this->calculateStripeFees($stripe, $subscription, $stripeAccount);
        
        $this->info("Calculated fees: {$fees} centesimi (€" . number_format($fees/100, 2) . ")");
        
        return Command::SUCCESS;
    }

    protected function calculateStripeFees($stripe, $subscription, $stripeAccount)
    {
        try {
            $totalFees = 0;
            
            $startTimestamp = $subscription->start_date;
            $endTimestamp = $subscription->start_date + (30 * 24 * 60 * 60);
            
            $this->info("Searching balance transactions from " . date('Y-m-d', $startTimestamp) . " to " . date('Y-m-d', $endTimestamp));
            
            $balanceTransactions = $stripe->balanceTransactions->all([
                'limit' => 50,
                'type' => 'charge',
                'created' => [
                    'gte' => $startTimestamp,
                    'lte' => $endTimestamp,
                ]
            ], ['stripe_account' => $stripeAccount->stripe_user_id]);

            $this->info("Found " . count($balanceTransactions->data) . " balance transactions");

            foreach ($balanceTransactions->data as $bt) {
                $this->info("BT: {$bt->id}, Type: {$bt->type}, Amount: {$bt->amount}, Fee: {$bt->fee}, Description: " . ($bt->description ?? 'N/A'));
                
                // Verifica se questa transaction è relativa alla nostra subscription
                if (stripos($bt->description ?? '', 'subscription') !== false || 
                    stripos($bt->description ?? '', $subscription->id) !== false) {
                    $totalFees += $bt->fee ?? 0;
                    $this->info("✓ MATCHED - Adding fee: {$bt->fee}");
                } else {
                    $this->warn("✗ NOT MATCHED");
                }
            }

            return $totalFees;
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 0;
        }
    }
}
