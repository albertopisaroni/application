<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;

class CheckAugustSubscriptions extends Command
{
    protected $signature = 'check:august-subscriptions';
    protected $description = 'Verifica abbonamenti previsti ad agosto';

    public function handle()
    {
        $companyId = $this->ask('Inserisci company_id da controllare');
        
        if (!$companyId) {
            $this->error('Company ID richiesto');
            return;
        }

        $this->info("=== VERIFICA ABBONAMENTI AGOSTO ===");
        $this->info("Company ID: {$companyId}");
        $this->newLine();

        // Rimuovo questa query che non serve

        $this->info("ABBONAMENTI CHE FINISCONO AD AGOSTO:");
        $endRenewals = Subscription::join('clients', 'subscriptions.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->whereMonth('subscriptions.current_period_end', 8)
            ->select('subscriptions.*')
            ->get();

        $endCount = $endRenewals->count();
        $endTotal = $endRenewals->sum('total_with_vat');
        
        $this->table(['ID', 'Cliente', 'Final Amount (centesimi)', 'Current Period End'], 
            $endRenewals->map(fn($s) => [
                $s->id,
                $s->client->name ?? 'N/A',
                $s->total_with_vat,
                $s->current_period_end
            ])->toArray()
        );
        
        $this->info("Totale che finiscono: {$endCount} abbonamenti");
        $this->info("Somma total_with_vat (centesimi): {$endTotal}");
        $this->info("Somma total_with_vat (euro): " . number_format($endTotal / 100, 2, ',', '.'));
        $this->newLine();

        $this->info("ABBONAMENTI CHE INIZIANO AD AGOSTO:");
        $startRenewals = Subscription::join('clients', 'subscriptions.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->whereMonth('subscriptions.start_date', 8)
            ->select('subscriptions.*')
            ->get();

        $startCount = $startRenewals->count();
        $startTotal = $startRenewals->sum('total_with_vat');
        
        $this->table(['ID', 'Cliente', 'Final Amount (centesimi)', 'Start Date'], 
            $startRenewals->map(fn($s) => [
                $s->id,
                $s->client->name ?? 'N/A',
                $s->total_with_vat,
                $s->start_date
            ])->toArray()
        );
        
        $this->info("Totale che iniziano: {$startCount} abbonamenti");
        $this->info("Somma total_with_vat (centesimi): {$startTotal}");
        $this->info("Somma total_with_vat (euro): " . number_format($startTotal / 100, 2, ',', '.'));
        $this->newLine();

        $this->info("=== RIEPILOGO TOTALE AGOSTO ===");
        $totalCount = $endCount + $startCount;
        $totalAmount = $endTotal + $startTotal;
        
        $this->info("Totale abbonamenti: {$totalCount}");
        $this->info("Totale importo (centesimi): {$totalAmount}");
        $this->info("Totale importo (euro): " . number_format($totalAmount / 100, 2, ',', '.'));
        
        return Command::SUCCESS;
    }
}
