<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Stripe\StripeClient;

class DebugStripeFees extends Command
{
    protected $signature = 'debug:stripe-fees {subscription_id}';
    protected $description = 'Debug Stripe fees calculation';

    public function handle()
    {
        $subId = $this->argument('subscription_id');
        $sub = Subscription::where('stripe_subscription_id', $subId)->first();
        
        if (!$sub) {
            $this->error('Subscription not found');
            return;
        }

        $stripe = new StripeClient(config('services.stripe.secret'));
        
        $this->info("=== DEBUGGING STRIPE FEES FOR {$subId} ===");
        
        try {
            // Step 1: Get subscription
            $stripeSub = $stripe->subscriptions->retrieve(
                $sub->stripe_subscription_id, 
                [], 
                ['stripe_account' => $sub->stripeAccount->stripe_user_id]
            );
            
            $this->info("Latest invoice: " . ($stripeSub->latest_invoice ?? 'NULL'));
            
            if (!$stripeSub->latest_invoice) {
                $this->warn("No latest invoice found");
                return;
            }

            // Step 2: Get all invoices
            $this->info("\n=== GETTING ALL INVOICES ===");
            $allInvoices = [];
            $params = ['limit' => 10, 'subscription' => $stripeSub->id];
            $invoices = $stripe->invoices->all($params, ['stripe_account' => $sub->stripeAccount->stripe_user_id]);

            $this->info("Found invoices: " . count($invoices->data));
            foreach($invoices->data as $i => $inv) {
                $this->info("Invoice {$i}: {$inv->id}, Status: {$inv->status}, Total: {$inv->total}");
                $allInvoices[] = $inv;
            }

            // Step 3: Check each invoice for payments
            $totalFees = 0;
            $this->info("\n=== CHECKING PAYMENTS ===");
            
            foreach ($allInvoices as $invoice) {
                $this->info("Checking invoice: {$invoice->id}, Status: {$invoice->status}");
                
                if ($invoice->status !== 'paid') {
                    $this->warn("Invoice not paid, skipping");
                    continue;
                }

                // Check invoice structure for payment info
                $invoiceArray = $invoice->toArray();
                $this->info("Looking for payment fields...");
                
                foreach($invoiceArray as $key => $value) {
                    if (stripos($key, 'charge') !== false || stripos($key, 'payment') !== false) {
                        $this->info("Found: {$key} = " . (is_array($value) ? json_encode($value) : $value));
                    }
                }
            }

            $this->info("\nTotal fees found: {$totalFees} centesimi");
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
