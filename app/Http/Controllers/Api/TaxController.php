<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tax;
use App\Models\Company;
use App\Services\TaxCalculationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxController extends Controller
{
    protected TaxCalculationService $taxService;

    public function __construct(TaxCalculationService $taxService)
    {
        $this->taxService = $taxService;
    }

    /**
     * GET /api/companies/{id}/taxes/{year}
     * Ritorna tutti i bollettini ordinati per due_date
     */
    public function index(Request $request, $companyId, $year)
    {
        $company = Company::findOrFail($companyId);
        
        // Verifica permessi
        // $this->authorize('view', $company);
        
        $taxes = $company->taxes()
            ->where('tax_year', $year)
            ->orderBy('due_date')
            ->orderBy('tax_type')
            ->get();

        // Calcola totali
        $summary = $this->calculateSummary($taxes);

        return response()->json([
            'data' => $taxes,
            'summary' => $summary,
            'meta' => [
                'company_id' => $companyId,
                'tax_year' => $year,
                'total_records' => $taxes->count()
            ]
        ]);
    }

    /**
     * GET /api/companies/{id}/tax-summary
     * Ritorna riepilogo con crediti, debiti, scadenze imminenti
     */
    public function summary(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        
        // $this->authorize('view', $company);
        
        // Scadenze imminenti (prossimi 30 giorni)
        $scadenzeImminenti = $company->taxes()
            ->pending()
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->get();

        // Scaduti
        $scaduti = $company->taxes()
            ->pending()
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'desc')
            ->get();

        // Crediti disponibili
        $crediti = $company->taxes()
            ->credits()
            ->get();

        // Totali per anno
        $totaliPerAnno = $company->taxes()
            ->select(
                'tax_year',
                DB::raw('SUM(CASE WHEN payment_status = "PENDING" THEN amount ELSE 0 END) as pending_amount'),
                DB::raw('SUM(CASE WHEN payment_status = "PAID" THEN amount ELSE 0 END) as paid_amount'),
                DB::raw('SUM(CASE WHEN payment_status = "CREDIT" THEN amount ELSE 0 END) as credit_amount')
            )
            ->groupBy('tax_year')
            ->orderBy('tax_year', 'desc')
            ->get();

        return response()->json([
            'scadenze_imminenti' => [
                'items' => $scadenzeImminenti,
                'totale' => $scadenzeImminenti->sum('amount'),
                'count' => $scadenzeImminenti->count()
            ],
            'scaduti' => [
                'items' => $scaduti,
                'totale' => $scaduti->sum('amount'),
                'count' => $scaduti->count()
            ],
            'crediti' => [
                'items' => $crediti,
                'totale' => $crediti->sum('amount'),
                'count' => $crediti->count()
            ],
            'totali_per_anno' => $totaliPerAnno,
            'meta' => [
                'company_id' => $companyId,
                'generated_at' => now()->toIso8601String()
            ]
        ]);
    }

    /**
     * POST /api/taxes/{id}/mark-paid
     * Marca un bollettino come pagato
     */
    public function markPaid(Request $request, $taxId)
    {
        $request->validate([
            'paid_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        $tax = Tax::findOrFail($taxId);
        
        // Verifica permessi
        // $this->authorize('update', $tax->company);

        if ($tax->payment_status === Tax::STATUS_PAID) {
            return response()->json([
                'message' => 'Il bollettino è già stato pagato',
                'data' => $tax
            ], 422);
        }

        if ($tax->payment_status === Tax::STATUS_CREDIT) {
            return response()->json([
                'message' => 'Non è possibile marcare un credito come pagato',
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $tax->markAsPaid(
                Carbon::parse($request->paid_date),
                $request->payment_reference
            );

            if ($request->has('notes')) {
                $tax->notes = $request->notes;
                $tax->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Bollettino marcato come pagato',
                'data' => $tax->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Errore durante l\'aggiornamento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/companies/{id}/taxes/calculate
     * Calcola le tasse per una company (alternativa al comando)
     */
    public function calculate(Request $request, $companyId)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'previous_acconti' => 'nullable|array',
            'previous_acconti.primo_acconto' => 'nullable|numeric|min:0',
            'previous_acconti.secondo_acconto' => 'nullable|numeric|min:0',
            'previous_credits' => 'nullable|array',
            'previous_credits.imposta' => 'nullable|numeric|min:0',
            'previous_credits.inps' => 'nullable|numeric|min:0',
        ]);

        $company = Company::findOrFail($companyId);
        
        // Verifica permessi
        // $this->authorize('update', $company);

        if (!$company->isRegimeForfettario()) {
            return response()->json([
                'message' => 'La company non è in regime forfettario RF19'
            ], 422);
        }

        if (!$company->total_revenue || $company->total_revenue == 0) {
            return response()->json([
                'message' => 'La company non ha fatturato registrato'
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Se è la prima elaborazione e sono forniti dati storici
            if (!$company->hasPreviousTaxRecords() && $request->has('previous_acconti')) {
                $acconti = $request->previous_acconti;
                $this->taxService->createPreviousYearAcconti(
                    $company,
                    $request->year - 1,
                    $acconti['primo_acconto'] ?? 0,
                    $acconti['secondo_acconto'] ?? 0
                );
            }

            if (!$company->hasPreviousTaxRecords() && $request->has('previous_credits')) {
                $credits = $request->previous_credits;
                $this->taxService->createPreviousYearCredits(
                    $company,
                    $request->year - 1,
                    $credits['imposta'] ?? 0,
                    $credits['inps'] ?? 0
                );
            }

            // Calcola le tasse
            $taxRecords = $this->taxService->calculateForCompany($company, $request->year);
            
            DB::commit();

            return response()->json([
                'message' => 'Calcolo tasse completato',
                'data' => $taxRecords,
                'summary' => $this->calculateSummary(collect($taxRecords))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Errore durante il calcolo delle tasse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/taxes/{id}
     * Cancella un bollettino (solo se PENDING)
     */
    public function destroy($taxId)
    {
        $tax = Tax::findOrFail($taxId);
        
        // Verifica permessi
        // $this->authorize('delete', $tax->company);

        if ($tax->payment_status !== Tax::STATUS_PENDING) {
            return response()->json([
                'message' => 'È possibile cancellare solo bollettini in stato PENDING'
            ], 422);
        }

        $tax->delete();

        return response()->json([
            'message' => 'Bollettino cancellato con successo'
        ]);
    }

    /**
     * PUT /api/taxes/{id}
     * Aggiorna un bollettino
     */
    public function update(Request $request, $taxId)
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        $tax = Tax::findOrFail($taxId);
        
        // Verifica permessi
        // $this->authorize('update', $tax->company);

        if ($tax->payment_status === Tax::STATUS_PAID) {
            return response()->json([
                'message' => 'Non è possibile modificare un bollettino già pagato'
            ], 422);
        }

        $tax->fill($request->only(['amount', 'due_date', 'description', 'notes']));
        $tax->save();

        return response()->json([
            'message' => 'Bollettino aggiornato con successo',
            'data' => $tax
        ]);
    }

    /**
     * Calcola il riepilogo dei totali
     */
    protected function calculateSummary($taxes)
    {
        $totaleImposta = $taxes
            ->filter(fn($r) => str_contains($r->tax_type, 'IMPOSTA_SOSTITUTIVA'))
            ->where('payment_status', '!=', Tax::STATUS_CREDIT)
            ->sum('amount');

        $totaleInps = $taxes
            ->filter(fn($r) => str_contains($r->tax_type, 'INPS'))
            ->where('payment_status', '!=', Tax::STATUS_CREDIT)
            ->sum('amount');

        $crediti = $taxes
            ->where('payment_status', Tax::STATUS_CREDIT)
            ->sum('amount');

        $pagati = $taxes
            ->where('payment_status', Tax::STATUS_PAID)
            ->sum('amount');

        $pendenti = $taxes
            ->where('payment_status', Tax::STATUS_PENDING)
            ->sum('amount');

        return [
            'totale_imposta' => $totaleImposta,
            'totale_inps' => $totaleInps,
            'totale_da_versare' => $pendenti,
            'totale_pagato' => $pagati,
            'crediti' => $crediti,
            'totale_generale' => $totaleImposta + $totaleInps
        ];
    }
}