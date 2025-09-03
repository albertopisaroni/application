<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RecurringInvoice extends Model
{
    protected static function boot()
    {
        parent::boot();
        static::creating(function($recurringInvoice){
            $recurringInvoice->uuid = Str::uuid();
        });
    }

    protected $fillable = [
        'company_id', 'client_id', 'numbering_id', 'payment_method_id',
        'template_name', 'header_notes', 'footer_notes', 'contact_info',
        'subtotal', 'vat', 'total', 'global_discount', 'withholding_tax', 'inps_contribution',
        'recurrence_type', 'recurrence_interval', 'start_date', 'end_date', 'next_invoice_date',
        'is_active', 'invoices_generated', 'max_invoices', 'last_generated_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_invoice_date' => 'date',
        'last_generated_at' => 'datetime',
        'is_active' => 'boolean',
        'withholding_tax' => 'boolean',
        'inps_contribution' => 'boolean',
        'subtotal' => 'decimal:2',
        'vat' => 'decimal:2',
        'total' => 'decimal:2',
        'global_discount' => 'decimal:2',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function numbering(): BelongsTo
    {
        return $this->belongsTo(InvoiceNumbering::class, 'numbering_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class);
    }

    public function generatedInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurring_invoice_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeReadyToGenerate($query)
    {
        return $query->active()
            ->where('next_invoice_date', '<=', now()->toDateString())
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where(function($q) {
                $q->whereNull('max_invoices')
                  ->orWhereRaw('invoices_generated < max_invoices');
            });
    }

    // Methods
    public function calculateNextInvoiceDate(): Carbon
    {
        $baseDate = $this->last_generated_at ? $this->last_generated_at->toDateString() : $this->start_date->toDateString();
        $date = Carbon::parse($baseDate);

        switch ($this->recurrence_type) {
            case 'days':
                return $date->addDays($this->recurrence_interval);
            case 'weeks':
                return $date->addWeeks($this->recurrence_interval);
            case 'months':
                return $date->addMonths($this->recurrence_interval);
            case 'years':
                return $date->addYears($this->recurrence_interval);
            default:
                return $date->addMonths(1);
        }
    }

    public function updateNextInvoiceDate(): void
    {
        $this->update([
            'next_invoice_date' => $this->calculateNextInvoiceDate(),
        ]);
    }

    public function canGenerateInvoice(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->next_invoice_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        if ($this->max_invoices && $this->invoices_generated >= $this->max_invoices) {
            return false;
        }

        return true;
    }

    public function getRecurrenceDescriptionAttribute(): string
    {
        $interval = $this->recurrence_interval == 1 ? '' : $this->recurrence_interval . ' ';
        
        switch ($this->recurrence_type) {
            case 'days':
                return "Ogni {$interval}" . ($this->recurrence_interval == 1 ? 'giorno' : 'giorni');
            case 'weeks':
                return "Ogni {$interval}" . ($this->recurrence_interval == 1 ? 'settimana' : 'settimane');
            case 'months':
                return "Ogni {$interval}" . ($this->recurrence_interval == 1 ? 'mese' : 'mesi');
            case 'years':
                return "Ogni {$interval}" . ($this->recurrence_interval == 1 ? 'anno' : 'anni');
            default:
                return 'Non definito';
        }
    }
}
