<?php

namespace App\Http\Controllers;

use App\Models\F24;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class F24Controller extends Controller
{
    /**
     * Mostra la lista degli F24
     */
    public function index(Request $request)
    {
        $company = Auth::user()->company;
        
        $query = $company->f24s()->with(['taxes']);
        
        // Filtri
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        if ($request->filled('section')) {
            $query->whereJsonContains('sections', $request->section);
        }
        
        if ($request->filled('year')) {
            $query->whereJsonContains('reference_years', $request->year);
        }
        
        // Ordinamento
        $query->orderBy('imported_at', 'desc');
        
        $f24s = $query->paginate(20);
        
        // Statistiche
        $stats = [
            'total' => $company->f24s()->count(),
            'pending' => $company->f24s()->where('payment_status', F24::STATUS_PENDING)->count(),
            'paid' => $company->f24s()->where('payment_status', F24::STATUS_PAID)->count(),
            'overdue' => $company->f24s()->where('payment_status', F24::STATUS_PENDING)
                ->where('due_date', '<', now())->count(),
        ];
        
        return view('f24.index', compact('f24s', 'stats'));
    }

    /**
     * Mostra i dettagli di un F24
     */
    public function show(F24 $f24)
    {
        $this->authorize('view', $f24);
        
        $f24->load(['taxes' => function($query) {
            $query->orderBy('section_type')->orderBy('tax_code');
        }]);
        
        // Raggruppa le tasse per sezione
        $taxesBySection = $f24->taxes->groupBy('section_type');
        
        return view('f24.show', compact('f24', 'taxesBySection'));
    }

    /**
     * Scarica il PDF dell'F24
     */
    public function download(F24 $f24)
    {
        $this->authorize('view', $f24);
        
        if (!$f24->s3_path || !Storage::disk('s3')->exists($f24->s3_path)) {
            abort(404, 'File non trovato');
        }
        
        $filename = $f24->filename;
        $content = Storage::disk('s3')->get($f24->s3_path);
        
        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Marca un F24 come pagato
     */
    public function markAsPaid(F24 $f24, Request $request)
    {
        $this->authorize('update', $f24);
        
        $f24->markAsPaid($request->input('paid_date'));
        
        return redirect()->back()->with('success', 'F24 marcato come pagato');
    }

    /**
     * Cancella un F24
     */
    public function destroy(F24 $f24)
    {
        $this->authorize('delete', $f24);
        
        // Cancella il file da S3
        if ($f24->s3_path) {
            Storage::disk('s3')->delete($f24->s3_path);
        }
        
        // Cancella l'F24 e tutte le tasse associate
        $f24->delete();
        
        return redirect()->route('f24.index')->with('success', 'F24 eliminato con successo');
    }

    /**
     * API: Lista F24 per AJAX
     */
    public function apiIndex(Request $request)
    {
        $company = Auth::user()->company;
        
        $query = $company->f24s()->with(['taxes']);
        
        // Filtri
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        if ($request->filled('section')) {
            $query->whereJsonContains('sections', $request->section);
        }
        
        if ($request->filled('year')) {
            $query->whereJsonContains('reference_years', $request->year);
        }
        
        // Ordinamento
        $query->orderBy('imported_at', 'desc');
        
        $f24s = $query->paginate($request->input('per_page', 20));
        
        return response()->json([
            'success' => true,
            'data' => $f24s->items(),
            'pagination' => [
                'current_page' => $f24s->currentPage(),
                'last_page' => $f24s->lastPage(),
                'per_page' => $f24s->perPage(),
                'total' => $f24s->total(),
            ]
        ]);
    }

    /**
     * API: Dettagli F24
     */
    public function apiShow(F24 $f24)
    {
        $this->authorize('view', $f24);
        
        $f24->load(['taxes' => function($query) {
            $query->orderBy('section_type')->orderBy('tax_code');
        }]);
        
        return response()->json([
            'success' => true,
            'data' => $f24
        ]);
    }

    /**
     * Upload ricevuta F24
     */
    public function uploadReceipt(Request $request, F24 $f24)
    {
        $this->authorize('update', $f24);
        
        $request->validate([
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
        ]);
        
        if ($f24->uploadReceipt($request->file('receipt'))) {
            return response()->json([
                'success' => true,
                'message' => 'Ricevuta caricata con successo',
                'receipt_filename' => $f24->receipt_filename,
                'receipt_uploaded_at' => $f24->receipt_uploaded_at->format('d/m/Y H:i'),
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Errore durante il caricamento della ricevuta'
        ], 500);
    }

    /**
     * Scarica la ricevuta F24
     */
    public function downloadReceipt(F24 $f24)
    {
        $this->authorize('view', $f24);
        
        if (!$f24->hasReceipt()) {
            abort(404, 'Ricevuta non trovata');
        }
        
        if (!Storage::disk('s3')->exists($f24->receipt_s3_path)) {
            abort(404, 'File ricevuta non trovato');
        }
        
        $content = Storage::disk('s3')->get($f24->receipt_s3_path);
        $filename = $f24->receipt_filename ?: 'ricevuta_f24.pdf';
        
        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Elimina la ricevuta F24
     */
    public function deleteReceipt(F24 $f24)
    {
        $this->authorize('update', $f24);
        
        if ($f24->deleteReceipt()) {
            return response()->json([
                'success' => true,
                'message' => 'Ricevuta eliminata con successo'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Errore durante l\'eliminazione della ricevuta'
        ], 500);
    }
}
