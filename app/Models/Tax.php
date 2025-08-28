<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Tax extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'f24_id',
        'section_type',
        'tax_year',
        'payment_year',
        'tax_type',
        'description',
        'tax_code',
        'amount',
        'due_date',
        'payment_status',
        'f24_url',
        'f24_generated_at',
        'paid_date',
        'payment_reference',
        'notes',
        'is_manual'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'f24_generated_at' => 'datetime',
        'is_manual' => 'boolean',
    ];

    // Tax Type Constants
    const TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO = 'IMPOSTA_SOSTITUTIVA_SALDO';
    const TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO = 'IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO';
    const TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO = 'IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO';
    const TAX_TYPE_IMPOSTA_SOSTITUTIVA_CREDITO = 'IMPOSTA_SOSTITUTIVA_CREDITO';
    const TAX_TYPE_INPS_SALDO = 'INPS_SALDO';
    const TAX_TYPE_INPS_PRIMO_ACCONTO = 'INPS_PRIMO_ACCONTO';
    const TAX_TYPE_INPS_SECONDO_ACCONTO = 'INPS_SECONDO_ACCONTO';
    const TAX_TYPE_INPS_TERZO_ACCONTO = 'INPS_TERZO_ACCONTO';
    const TAX_TYPE_INPS_QUARTO_ACCONTO = 'INPS_QUARTO_ACCONTO';
    const TAX_TYPE_INPS_CREDITO = 'INPS_CREDITO';
    const TAX_TYPE_INPS_FISSI_SALDO = 'INPS_FISSI_SALDO';
    const TAX_TYPE_INPS_FISSI_PRIMO_ACCONTO = 'INPS_FISSI_PRIMO_ACCONTO';
    const TAX_TYPE_INPS_FISSI_SECONDO_ACCONTO = 'INPS_FISSI_SECONDO_ACCONTO';
    const TAX_TYPE_INPS_FISSI_TERZO_ACCONTO = 'INPS_FISSI_TERZO_ACCONTO';
    const TAX_TYPE_INPS_FISSI_QUARTO_ACCONTO = 'INPS_FISSI_QUARTO_ACCONTO';
    const TAX_TYPE_INPS_PERCENTUALI_SALDO = 'INPS_PERCENTUALI_SALDO';
    const TAX_TYPE_INPS_PERCENTUALI_PRIMO_ACCONTO = 'INPS_PERCENTUALI_PRIMO_ACCONTO';
    const TAX_TYPE_INPS_PERCENTUALI_SECONDO_ACCONTO = 'INPS_PERCENTUALI_SECONDO_ACCONTO';
    const TAX_TYPE_SANZIONI = 'SANZIONI';
    const TAX_TYPE_INTERESSI = 'INTERESSI';
    const TAX_TYPE_DIRITTO_ANNUALE_CCIAA = 'DIRITTO_ANNUALE_CCIAA';

    // Payment Status Constants
    const STATUS_PENDING = 'PENDING';
    const STATUS_PAID = 'PAID';
    const STATUS_OVERDUE = 'OVERDUE';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_CREDIT = 'CREDIT';

    // Tax Codes for F24
    const TAX_CODE_IMPOSTA_SOSTITUTIVA_SALDO = '1792'; // Saldo imposta sostitutiva
    const TAX_CODE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO = '1790'; // Primo acconto imposta sostitutiva
    const TAX_CODE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO = '1791'; // Secondo acconto imposta sostitutiva
    const TAX_CODE_INPS_GESTIONE_SEPARATA = 'P10'; // Gestione separata
    const TAX_CODE_INPS_FISSI = 'CF'; // Contributi fissi unificato (2025)
    const TAX_CODE_INPS_FISSI_COMERCIANTI = 'CF'; // Contributi fissi commercianti
    const TAX_CODE_INPS_FISSI_ARTIGIANI = 'AF'; // Contributi fissi artigiani
    const TAX_CODE_INPS_FISSI_PREGRESSI_COMERCIANTI = 'CFP'; // Contributi fissi anni pregressi commercianti
    const TAX_CODE_INPS_FISSI_PREGRESSI_ARTIGIANI = 'AFP'; // Contributi fissi anni pregressi artigiani
    const TAX_CODE_INPS_PERCENTUALI_COMERCIANTI = 'CP'; // Contributi percentuali commercianti
    const TAX_CODE_INPS_PERCENTUALI_ARTIGIANI = 'AP'; // Contributi percentuali artigiani
    const TAX_CODE_INPS_PERCENTUALI_PREGRESSI_COMERCIANTI = 'CPP'; // Contributi percentuali anni pregressi commercianti
    const TAX_CODE_INPS_PERCENTUALI_PREGRESSI_ARTIGIANI = 'APP'; // Contributi percentuali anni pregressi artigiani
    const TAX_CODE_INPS_PERCENTUALI_SALDO = 'CPP'; // Saldo contributi INPS percentuali
    const TAX_CODE_INPS_PERCENTUALI_ACCONTO = 'CP'; // Acconti contributi INPS percentuali
    const TAX_CODE_SANZIONI = '8944'; // Sanzioni per ravvedimento (imposta sostitutiva)
    const TAX_CODE_INTERESSI = '1944'; // Interessi moratori (imposta sostitutiva)
    const TAX_CODE_SANZIONI_INPS = '1989'; // Sanzioni INPS per ravvedimento
    const TAX_CODE_INTERESSI_INPS = '1990'; // Interessi INPS per ravvedimento
    const TAX_CODE_DIRITTO_ANNUALE_CCIAA = '3850'; // Diritto annuale iscrizione CCIAA

    // Section Type Constants
    const SECTION_TYPE_ERARIO = 'erario';
    const SECTION_TYPE_INPS = 'inps';
    const SECTION_TYPE_IMU = 'imu';
    const SECTION_TYPE_ALTRI = 'altri';

    public static function getSectionTypes(): array
    {
        return [
            self::SECTION_TYPE_ERARIO,
            self::SECTION_TYPE_INPS,
            self::SECTION_TYPE_IMU,
            self::SECTION_TYPE_ALTRI,
        ];
    }

    public static function getTaxTypes(): array
    {
        return [
            self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO,
            self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO,
            self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO,
            self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_CREDITO,
            self::TAX_TYPE_INPS_SALDO,
            self::TAX_TYPE_INPS_PRIMO_ACCONTO,
            self::TAX_TYPE_INPS_SECONDO_ACCONTO,
            self::TAX_TYPE_INPS_TERZO_ACCONTO,
            self::TAX_TYPE_INPS_QUARTO_ACCONTO,
            self::TAX_TYPE_INPS_CREDITO,
        ];
    }

    public static function getPaymentStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
            self::STATUS_CREDIT,
        ];
    }

    /**
     * Relazioni
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function f24()
    {
        return $this->belongsTo(F24::class);
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

    public function scopeCredits($query)
    {
        return $query->where('payment_status', self::STATUS_CREDIT);
    }

    public function scopeByTaxYear($query, $year)
    {
        return $query->where('tax_year', $year);
    }

    public function scopeByPaymentYear($query, $year)
    {
        return $query->where('payment_year', $year);
    }

    public function scopeImpostaSostitutiva($query)
    {
        return $query->whereIn('tax_type', [
            self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO,
            self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO,
            self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO,
            self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_CREDITO
        ]);
    }

    public function scopeInps($query)
    {
        return $query->whereIn('tax_type', [
            self::TAX_TYPE_INPS_SALDO,
            self::TAX_TYPE_INPS_PRIMO_ACCONTO,
            self::TAX_TYPE_INPS_SECONDO_ACCONTO,
            self::TAX_TYPE_INPS_TERZO_ACCONTO,
            self::TAX_TYPE_INPS_QUARTO_ACCONTO,
            self::TAX_TYPE_INPS_CREDITO
        ]);
    }

    /**
     * Metodi helper
     */
    public function isOverdue(): bool
    {
        return $this->payment_status === self::STATUS_PENDING && 
               $this->due_date->isPast();
    }

    public function markAsPaid($paidDate = null, $reference = null): void
    {
        $this->payment_status = self::STATUS_PAID;
        $this->paid_date = $paidDate ?? now();
        $this->payment_reference = $reference;
        $this->save();
        
        // Aggiorna lo stato dell'F24 associato
        if ($this->f24) {
            $this->f24->updatePaymentStatus();
        }
    }

    public function cancel(): void
    {
        $this->payment_status = self::STATUS_CANCELLED;
        $this->save();
        
        // Aggiorna lo stato dell'F24 associato
        if ($this->f24) {
            $this->f24->updatePaymentStatus();
        }
    }

    public function getFormattedAmount(): string
    {
        return '€ ' . number_format($this->amount, 2, ',', '.');
    }

    public function getTaxCodeForF24(): string
    {
        switch ($this->tax_type) {
            case self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SALDO:
                return self::TAX_CODE_IMPOSTA_SOSTITUTIVA_SALDO;
            case self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO:
                return self::TAX_CODE_IMPOSTA_SOSTITUTIVA_PRIMO_ACCONTO;
            case self::TAX_TYPE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO:
                return self::TAX_CODE_IMPOSTA_SOSTITUTIVA_SECONDO_ACCONTO;
            case self::TAX_TYPE_INPS_SALDO:
            case self::TAX_TYPE_INPS_PRIMO_ACCONTO:
            case self::TAX_TYPE_INPS_SECONDO_ACCONTO:
                // Determina il codice in base al tipo di gestione
                if ($this->company->gestione_separata) {
                    return self::TAX_CODE_INPS_GESTIONE_SEPARATA;
                }
                // Per commercianti/artigiani potrebbe essere necessario distinguere
                return self::TAX_CODE_INPS_ARTIGIANI;
            default:
                return $this->tax_code;
        }
    }
}