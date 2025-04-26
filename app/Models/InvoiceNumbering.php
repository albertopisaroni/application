<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceNumbering extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'prefix',
        'current_number',
        'name',
        'template_id',
        'logo_base64',        
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
            $this->current_number = 0;
            $this->last_invoice_year = $invoiceYear;
        }
    
        $this->current_number++;
        $this->save();
    
        return $this->type === 'custom' && !empty($this->prefix)
            ? $this->prefix . $this->current_number
            : (string) $this->current_number;
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

        // Altrimenti, restituisci current_number + 1 senza modificare il DB.
        $next = $this->current_number + 1;
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
        return $this->current_number + 1;
    }
}