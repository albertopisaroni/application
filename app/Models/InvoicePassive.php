<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InvoicePassive extends Model
{
    protected $table = 'invoices_passive';

    protected static function boot()
    {
        parent::boot();
        static::creating(function($invoice){
            $invoice->uuid = Str::uuid();
        });
    }

    protected $fillable = [
        'company_id', 'supplier_id', 'invoice_number', 'document_type', 'original_invoice_id',
        'issue_date', 'data_accoglienza_file', 'fiscal_year', 'withholding_tax', 'inps_contribution',
        'payment_method_id', 'subtotal', 'vat', 'total', 'global_discount', 'header_notes',
        'footer_notes', 'contact_info', 'pdf_path', 'pdf_url', 'xml_payload', 'sdi_uuid',
        'sdi_filename', 'sdi_status', 'sdi_error', 'sdi_error_description', 'sdi_received_at',
        'sdi_processed_at', 'is_processed', 'is_paid', 'imported_from_callback',
        'has_attachments', 'attachments_count', 'primary_attachment_path', 'primary_attachment_filename', 'attachments_summary',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'data_accoglienza_file' => 'date',
        'fiscal_year' => 'integer',
        'withholding_tax' => 'boolean',
        'inps_contribution' => 'boolean',
        'subtotal' => 'decimal:2',
        'vat' => 'decimal:2',
        'total' => 'decimal:2',
        'global_discount' => 'decimal:2',
        'xml_payload' => 'array',
        'sdi_received_at' => 'datetime',
        'sdi_processed_at' => 'datetime',
        'is_processed' => 'boolean',
        'is_paid' => 'boolean',
        'imported_from_callback' => 'boolean',
        'has_attachments' => 'boolean',
        'attachments_count' => 'integer',
        'attachments_summary' => 'array',
    ];

    // Relazioni
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'supplier_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoicePassiveItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePassivePayment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InvoicePassiveAttachment::class);
    }

    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(InvoicePassive::class, 'original_invoice_id');
    }

    // Scopes
    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessor per il totale pagato
    public function getTotalPaidAttribute()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    // Accessor per il saldo rimanente
    public function getRemainingBalanceAttribute()
    {
        return $this->total - $this->total_paid;
    }

    // Metodi helper
    public function markAsProcessed(): bool
    {
        return $this->update([
            'is_processed' => true,
            'sdi_processed_at' => now(),
        ]);
    }

    public function markAsPaid(): bool
    {
        return $this->update([
            'is_paid' => true,
        ]);
    }

    // Metodi per la gestione degli allegati
    public function updateAttachmentsSummary(): void
    {
        $attachments = $this->attachments;
        
        $primaryAttachment = $attachments->where('is_primary', true)->first();
        
        $summary = $attachments->map(function ($attachment) {
            return [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'type' => $attachment->attachment_type,
                'size' => $attachment->file_size,
                'size_formatted' => $attachment->formatted_file_size,
                'is_primary' => $attachment->is_primary,
                's3_path' => $attachment->s3_path,
            ];
        })->toArray();

        $this->update([
            'has_attachments' => $attachments->count() > 0,
            'attachments_count' => $attachments->count(),
            'primary_attachment_path' => $primaryAttachment?->s3_path,
            'primary_attachment_filename' => $primaryAttachment?->filename,
            'attachments_summary' => $summary,
        ]);
    }

    // Accessor per il PDF principale
    public function getPrimaryPdfAttribute()
    {
        if (!$this->has_attachments || !$this->primary_attachment_path) {
            return null;
        }

        return $this->attachments()->where('is_primary', true)->where('attachment_type', 'pdf')->first();
    }

    // Accessor per tutti i PDF
    public function getPdfAttachmentsAttribute()
    {
        return $this->attachments()->where('attachment_type', 'pdf')->get();
    }

    // Scope per fatture con allegati
    public function scopeWithAttachments($query)
    {
        return $query->where('has_attachments', true);
    }

    public function scopeWithPdf($query)
    {
        return $query->where('has_attachments', true)
                    ->whereNotNull('primary_attachment_path');
    }
}
