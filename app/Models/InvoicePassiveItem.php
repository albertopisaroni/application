<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePassiveItem extends Model
{
    protected $fillable = [
        'invoice_passive_id', 'line_number', 'name', 'description', 'quantity',
        'unit_of_measure', 'unit_price', 'line_total', 'vat_rate', 'vat_amount',
        'product_code', 'period_start', 'period_end', 'discount_data', 'additional_data',
    ];

    protected $casts = [
        'quantity' => 'decimal:5',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'discount_data' => 'array',
        'additional_data' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoicePassive::class, 'invoice_passive_id');
    }

    // Calcola automaticamente il totale riga
    public function calculateLineTotal(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }

    // Calcola automaticamente l'IVA
    public function calculateVatAmount(): float
    {
        return round($this->line_total * ($this->vat_rate / 100), 2);
    }
}
