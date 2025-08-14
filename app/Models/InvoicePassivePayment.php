<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePassivePayment extends Model
{
    protected $fillable = [
        'invoice_passive_id', 'payment_method_id', 'amount', 'payment_date', 'due_date',
        'reference', 'notes', 'iban', 'bank_name', 'transaction_id', 'status', 'is_verified',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
        'is_verified' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoicePassive::class, 'invoice_passive_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // Metodi helper
    public function markAsCompleted(): bool
    {
        return $this->update(['status' => 'completed']);
    }

    public function markAsVerified(): bool
    {
        return $this->update(['is_verified' => true]);
    }
}
