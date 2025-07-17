<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class InvoiceNumbering extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'prefix',
        'current_number_invoice',
        'current_number_autoinvoice',
        'current_number_credit',
        'default_header_notes',
        'default_footer_notes',
        'default_payment_method_id',
        'name',
        'template_invoice_id',
        'template_autoinvoice_id',
        'template_credit_id',
        'logo_base64',
        'logo_base64_square',
    ];

    // Ogni numerazione appartiene a una società
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    /**
     * Incrementa e restituisce il prossimo numero.
     * Per esempio, per una numerazione standard restituisce 1, 2, 3, ...  
     * Per una numerazione personalizzata restituisce il prefisso concatenato al progressivo.
     */
    public function nextNumber($invoiceYear = null)
    {
        $invoiceYear = $invoiceYear ?: date('Y');
    
        // Se l'anno dell'ultima fattura differisce dall'anno corrente, resetta il progressivo.
        if ($this->last_invoice_year != $invoiceYear) {
            $this->current_number_invoice = 0;
            $this->last_invoice_year = $invoiceYear;
        }
    
        $this->current_number_invoice++;
        $this->save();
    
        return $this->type === 'custom' && !empty($this->prefix)
            ? $this->prefix . $this->current_number_invoice
            : (string) $this->current_number_invoice;
    }

    public function getNextNumber()
    {
        $currentYear = date('Y');
        
        // Se l'anno corrente è diverso da last_invoice_year, il prossimo numero è 1.
        if ($this->last_invoice_year != $currentYear) {
            return $this->type === 'custom' && !empty($this->prefix)
                ? $this->prefix . '1'
                : '1';
        }

        // Altrimenti, restituisci current_number_invoice + 1 senza modificare il DB.
        $next = $this->current_number_invoice + 1;
        return $this->type === 'custom' && !empty($this->prefix)
            ? $this->prefix . $next
            : (string) $next;
    }

    public function getNextNumericPart()
    {
        $currentYear = date('Y');
        
        if ($this->last_invoice_year != $currentYear) {
            return 1;
        }
        return $this->current_number_invoice + 1;
    }

    public function defaultPaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'default_payment_method_id');
    }


    public function templateInvoice()
    {
        return $this->belongsTo(InvoiceTemplate::class, 'template_invoice_id');
    }

    public function templateAutoinvoice()
    {
        return $this->belongsTo(InvoiceTemplate::class, 'template_autoinvoice_id');
    }

    public function templateCredit()
    {
        return $this->belongsTo(InvoiceTemplate::class, 'template_credit_id');
    }


    public function getLogoAttribute(): string
    {
        if ($this->type === 'standard' && !$this->logo_square_path) {
            return $this->company->logo;
        }

        if (!empty($this->logo_square_path) && Storage::disk('s3')->exists($this->logo_square_path)) {
            return Storage::disk('s3')->temporaryUrl(
                $this->logo_square_path, now()->addMinutes(5)
            );
        }

        return 'https://ui-avatars.com/api/?format=svg&name=' . urlencode($this->name);
    }

    public function stripeAccount()
    {
        return $this->hasOne(StripeAccount::class);
    }
}