<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InvoicePaymentSchedule;

class Invoice extends Model
{

    protected static function boot()
    {
        parent::boot();
        static::creating(function($invoice){
            $invoice->uuid = \Str::uuid();
        });
    }

    protected $fillable = [
        'company_id', 'client_id', 'numbering_id', 'invoice_number', 'issue_date', 'document_type', 'contact_info',
        'original_invoice_id', 'data_accoglienza_file', 'fiscal_year', 'withholding_tax', 'inps_contribution', 'payment_methods_id',
        'subtotal', 'vat', 'total', 'global_discount', 'header_notes', 'footer_notes',
        'save_notes_for_future', 'pdf_path', 'sdi_uuid', 'sdi_id_invio', 'sdi_status', 'payment_method_id',
        'sdi_error', 'sdi_error_description', 'sdi_sent_at', 'sdi_received_at', 'sdi_attempt', 'imported_from_ae',
    ];

    // Se gli items sono in JSON, puoi farli castare come array:
    protected $casts = [
        'issue_date'           => 'date',
        'data_accoglienza_file'=> 'date',
        'sdi_sent_at'          => 'datetime',
        'sdi_received_at'      => 'datetime',
        'imported_from_ae'     => 'boolean',
    ];

    // Relazione con la società (se necessario)
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(InvoicePaymentSchedule::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function numbering()
    {
        return $this->belongsTo(InvoiceNumbering::class, 'numbering_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function originalInvoice()
    {
        return $this->belongsTo(Invoice::class, 'original_invoice_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(Invoice::class, 'original_invoice_id');
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->total <= 0) {
            return 'Pagata';
        }
    
        $paid = $this->payments()->sum('amount');
    
        if ($paid <= 0) return 'Non pagata';
        if ($paid < $this->total) return 'Parziale';
    
        return 'Pagata';
    }

    public function getDeadlineDateAttribute()
    {
        $date = $this->paymentSchedules()
            ->orderByDesc('due_date')
            ->value('due_date');

        return $date ? \Carbon\Carbon::parse($date) : null;
    }

    public function getNettoPostTaxAttribute(): float
    {
        $company           = $this->company;
        $totale            = $this->total;
        
        if ($totale <= 0) return 0.00;
    
        $anno              = $this->issue_date->year;
        $coeff             = $company->coefficiente / 100;
        $aliquotaImposta   = $company->startup ? 0.05 : 0.15;
    
        // imponibile forfettario
        $imponibile        = round($totale * $coeff, 2);
    
        // bollo
        $bollo             = $totale > 77 ? 2 : 0;
    
        // fatturato annuo (e fallback se zero)
        $fatturatoAnnuale  = Invoice::where('company_id', $company->id)
                                  ->whereYear('issue_date', $anno)
                                  ->sum('total');
        $fatturatoAnnuale  = $fatturatoAnnuale > 0
                          ? $fatturatoAnnuale
                          : $totale;
    
        // quota fissa proporzionale
        $contributiFissi   = $company->gestione_separata ? 0 : 4200;
        $quotaFissa        = round(($totale / $fatturatoAnnuale) * $contributiFissi, 2);
    
        if ($company->gestione_separata) {
            // gestione separata
            $inpsPercentuale = round($imponibile * 0.2607, 2);
            $inps            = $inpsPercentuale;
            $imposta         = round($imponibile * $aliquotaImposta, 2);
        } else {
            // artigiani / commercianti
            $inpsPercentuale = round($imponibile * 0.24, 2);
            $inps            = $inpsPercentuale + $quotaFissa;
            // IRPEF sul solo imponibile netto INPS%
            $impostaBase     = $imponibile - $inpsPercentuale;
            $imposta         = round($impostaBase * $aliquotaImposta, 2);
        }
    
        // calcolo netto
        $netto = $totale
               - $inps
               - $imposta
               - $bollo;
    
        return round($netto, 2);
    }
}
