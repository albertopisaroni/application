<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;

class CheckCurrentMonth extends Command
{
    protected $signature = 'check:current-month';
    protected $description = 'Verifica calcolo mese corrente come nel frontend';

    public function handle()
    {
        $companyId = $this->ask('Inserisci company_id da controllare');
        
        if (!$companyId) {
            $this->error('Company ID richiesto');
            return;
        }

        $this->info("=== VERIFICA MESE CORRENTE (LOGICA FRONTEND) ===");
        $this->info("Company ID: {$companyId}");
        $this->info("Mese corrente: " . Carbon::now()->format('Y-m'));
        $this->newLine();

        // Stesso calcolo di SubscriptionList.php
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        $this->info("Periodo: {$startOfMonth->format('Y-m-d')} - {$endOfMonth->format('Y-m-d')}");
        $this->newLine();

        // 1) Prepara la query base con JOIN su clients
        $subQuery = Subscription::join('clients', 'subscriptions.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId);

        // 2) Rinnovi che finiscono questo mese
        $endRenewals = (clone $subQuery)
            ->whereBetween('subscriptions.current_period_end', [$startOfMonth, $endOfMonth]);

        // 3) Rinnovi che partono questo mese
        $startRenewals = (clone $subQuery)
            ->whereBetween('subscriptions.start_date', [$startOfMonth, $endOfMonth]);

        $this->info("ABBONAMENTI CHE FINISCONO QUESTO MESE:");
        $endData = $endRenewals->select('subscriptions.*')->get();
        
        $this->table(['ID', 'Cliente', 'Final Amount (centesimi)', 'Current Period End'], 
            $endData->map(fn($s) => [
                $s->id,
                $s->client->name ?? 'N/A',
                $s->total_with_vat,
                $s->current_period_end
            ])->toArray()
        );
        
        $endCount = $endData->count();
        $endTotal = $endData->sum('total_with_vat');
        
        $this->info("Count: {$endCount}");
        $this->info("Sum (centesimi): {$endTotal}");
        $this->info("Sum (euro): " . number_format($endTotal / 100, 2, ',', '.'));
        $this->newLine();

        $this->info("ABBONAMENTI CHE INIZIANO QUESTO MESE:");
        $startData = $startRenewals->select('subscriptions.*')->get();
        
        $this->table(['ID', 'Cliente', 'Final Amount (centesimi)', 'Start Date'], 
            $startData->map(fn($s) => [
                $s->id,
                $s->client->name ?? 'N/A',
                $s->total_with_vat,
                $s->start_date
            ])->toArray()
        );
        
        $startCount = $startData->count();
        $startTotal = $startData->sum('total_with_vat');
        
        $this->info("Count: {$startCount}");
        $this->info("Sum (centesimi): {$startTotal}");
        $this->info("Sum (euro): " . number_format($startTotal / 100, 2, ',', '.'));
        $this->newLine();

        // 4) Conta e somma (come nel codice originale)
        $renewalsCount = $endCount + $startCount;
        $renewalsTotal = ($endTotal + $startTotal) / 100; // Converti da centesimi a euro

        $this->info("=== RISULTATO FINALE (COME NEL FRONTEND) ===");
        $this->info("renewalsCount: {$renewalsCount}");
        $this->info("renewalsTotal: " . number_format($renewalsTotal, 2, ',', '.') . " EUR");
        
        return Command::SUCCESS;
    }
}
