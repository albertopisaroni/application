<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StripeAccount;
use Illuminate\Support\Facades\Artisan;

class StripeBatchSyncCommand extends Command
{
    protected $signature = 'stripe:batch-sync {--limit=10 : Numero massimo di account da sincronizzare} {--company_id= : ID specifico della company da sincronizzare}';
    protected $description = 'Sincronizza tutti gli account Stripe in batch (per sincronizzazione iniziale)';

    public function handle()
    {
        $limit = $this->option('limit');
        $companyId = $this->option('company_id');

        $this->info("🚀 Inizio sincronizzazione batch Stripe");

        $query = StripeAccount::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $accounts = $query->limit($limit)->get();

        if ($accounts->isEmpty()) {
            $this->info('Nessun account Stripe trovato da sincronizzare.');
            return Command::SUCCESS;
        }

        $this->info("Trovati {$accounts->count()} account da sincronizzare");

        $bar = $this->output->createProgressBar($accounts->count());
        $bar->start();

        $successful = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                $this->info("\n🔄 Sincronizzando account: {$account->stripe_user_id} (Company: {$account->company_id})");
                
                $exitCode = Artisan::call('stripe:sync', [
                    'stripe_account_id' => $account->id,
                    '--initial' => true
                ]);

                if ($exitCode === Command::SUCCESS) {
                    $successful++;
                    $this->info("✅ Account {$account->stripe_user_id} sincronizzato con successo");
                } else {
                    $failed++;
                    $this->error("❌ Errore nella sincronizzazione dell'account {$account->stripe_user_id}");
                }

            } catch (\Exception $e) {
                $failed++;
                $this->error("❌ Eccezione per account {$account->stripe_user_id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);
        $this->info("📊 Sincronizzazione completata:");
        $this->info("✅ Successi: {$successful}");
        $this->info("❌ Fallimenti: {$failed}");

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
