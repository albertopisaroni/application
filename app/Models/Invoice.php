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
        'company_id', 'client_id', 'numbering_id', 'invoice_number', 'issue_date',
        'fiscal_year', 'withholding_tax', 'inps_contribution', 'payment_methods_id',
        'subtotal', 'vat', 'total', 'global_discount', 'header_notes', 'footer_notes',
        'save_notes_for_future', 'pdf_path', 'sdi_uuid', 'sdi_status', 'payment_method_id',
        'sdi_error', 'sdi_error_description', 'sdi_sent_at', 'sdi_received_at', 'sdi_attempt',
    ];

    // Se gli items sono in JSON, puoi farli castare come array:
    protected $casts = [
        'issue_date'      => 'date',
        'sdi_sent_at'     => 'datetime',
        'sdi_received_at' => 'datetime',
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
}