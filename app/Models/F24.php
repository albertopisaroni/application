<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;

class F24 extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'f24s';

    protected $fillable = [
        'company_id',
        'filename',
        's3_path',
        's3_url',
        'receipt_s3_path',
        'receipt_filename',
        'receipt_uploaded_at',
        'total_amount',
        'due_date',
        'payment_status',
        'payment_reference',
        'sections',
        'reference_years',
        'notes',
        'imported_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_date' => 'date',
        'sections' => 'array',
        'reference_years' => 'array',
        'imported_at' => 'datetime',
        'receipt_uploaded_at' => 'datetime',
    ];

    // Payment Status Constants
    const STATUS_PENDING = 'PENDING';
    const STATUS_PAID = 'PAID';
    const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    const STATUS_OVERDUE = 'OVERDUE';
    const STATUS_CANCELLED = 'CANCELLED';

    // Section Types
    const SECTION_ERARIO = 'erario';
    const SECTION_INPS = 'inps';
    const SECTION_IMU = 'imu';
    const SECTION_ALTRI = 'altri';

    public static function getPaymentStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
        ];
    }

    public static function getSections(): array
    {
        return [
            self::SECTION_ERARIO,
            self::SECTION_INPS,
            self::SECTION_IMU,
            self::SECTION_ALTRI,
        ];
    }

    /**
     * Relazioni
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }

    public function getTaxesCount(): int
    {
        return $this->taxes()->count();
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', self::STATUS_PAID);
    }

    public function scopeByReferenceYear($query, $year)
    {
        return $query->whereJsonContains('reference_years', $year);
    }

    public function scopeBySection($query, $section)
    {
        return $query->whereJsonContains('sections', $section);
    }

    /**
     * Metodi helper
     */
    public function isOverdue(): bool
    {
        return $this->payment_status === self::STATUS_PENDING && 
               $this->due_date && $this->due_date->isPast();
    }

    public function markAsPaid($paidDate = null, $reference = null): void
    {
        \Log::info('F24::markAsPaid chiamato', [
            'f24_id' => $this->id,
            'current_status' => $this->payment_status,
            'paid_date' => $paidDate,
            'reference' => $reference
        ]);
        
        $this->payment_status = self::STATUS_PAID;
        $this->payment_reference = $reference;
        $this->save();
        
        \Log::info('F24 status aggiornato', [
            'f24_id' => $this->id,
            'new_status' => $this->payment_status
        ]);
        
        // Marca anche tutte le tasse associate come pagate
        $taxesUpdated = $this->taxes()->update([
            'payment_status' => Tax::STATUS_PAID,
            'paid_date' => $paidDate ?? now(),
            'payment_reference' => $reference
        ]);
        
        \Log::info('Tasse aggiornate', [
            'f24_id' => $this->id,
            'taxes_updated' => $taxesUpdated
        ]);
    }

    public function cancel(): void
    {
        $this->payment_status = self::STATUS_CANCELLED;
        $this->save();
        
        // Cancella anche tutte le tasse associate
        $this->taxes()->update(['payment_status' => Tax::STATUS_CANCELLED]);
    }

    public function getFormattedAmount(): string
    {
        return '€ ' . number_format($this->total_amount, 2, ',', '.');
    }

    public function getSectionsList(): string
    {
        return implode(', ', array_map('ucfirst', $this->sections ?? []));
    }

    public function getReferenceYearsList(): string
    {
        return implode(', ', $this->reference_years ?? []);
    }

    public function hasSection(string $section): bool
    {
        return in_array($section, $this->sections ?? []);
    }

    public function getTaxesBySection(string $section)
    {
        return $this->taxes()->where('section_type', $section)->get();
    }

    public function getPaidTaxesCount(): int
    {
        return $this->taxes()->where('payment_status', Tax::STATUS_PAID)->count();
    }

    public function getPendingTaxesCount(): int
    {
        return $this->taxes()->where('payment_status', Tax::STATUS_PENDING)->count();
    }

    public function getCancelledTaxesCount(): int
    {
        return $this->taxes()->where('payment_status', Tax::STATUS_CANCELLED)->count();
    }

    public function getTotalTaxesCount(): int
    {
        return $this->taxes()->count();
    }

    /**
     * Aggiorna lo stato di pagamento dell'F24 basato sulle tasse
     */
    public function updatePaymentStatus(): void
    {
        $totalTaxes = $this->getTotalTaxesCount();
        $paidTaxes = $this->getPaidTaxesCount();
        $pendingTaxes = $this->getPendingTaxesCount();
        $cancelledTaxes = $this->getCancelledTaxesCount();

        // Se tutte le tasse sono pagate, l'F24 è pagato
        if ($paidTaxes === $totalTaxes) {
            $this->payment_status = self::STATUS_PAID;
        }
        // Se tutte le tasse sono cancellate, l'F24 è cancellato
        elseif ($cancelledTaxes === $totalTaxes) {
            $this->payment_status = self::STATUS_CANCELLED;
        }
        // Se ci sono tasse pagate ma non tutte, l'F24 è parzialmente pagato
        elseif ($paidTaxes > 0 && $paidTaxes < $totalTaxes) {
            $this->payment_status = self::STATUS_PARTIALLY_PAID;
        }
        // Altrimenti è in attesa
        else {
            $this->payment_status = self::STATUS_PENDING;
        }

        $this->save();
    }

    /**
     * Metodi per la gestione delle ricevute
     */
    public function hasReceipt(): bool
    {
        return !empty($this->receipt_s3_path);
    }

    public function getReceiptUrl(): ?string
    {
        if (!$this->hasReceipt()) {
            return null;
        }
        
        return Storage::disk('s3')->url($this->receipt_s3_path);
    }

    public function uploadReceipt($file): bool
    {
        try {
            $filename = 'receipt_' . time() . '_' . $file->getClientOriginalName();
            $path = 'f24_receipts/' . $this->company_id . '/' . $filename;
            
            // Upload su S3
            Storage::disk('s3')->put($path, file_get_contents($file));
            
            // Aggiorna il modello
            $this->update([
                'receipt_s3_path' => $path,
                'receipt_filename' => $file->getClientOriginalName(),
                'receipt_uploaded_at' => now(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Errore upload ricevuta F24', [
                'f24_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deleteReceipt(): bool
    {
        try {
            if ($this->receipt_s3_path) {
                Storage::disk('s3')->delete($this->receipt_s3_path);
            }
            
            $this->update([
                'receipt_s3_path' => null,
                'receipt_filename' => null,
                'receipt_uploaded_at' => null,
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Errore eliminazione ricevuta F24', [
                'f24_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
