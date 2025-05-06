<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class InvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'method',
        'note',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            $payment->validateAmount();
            $payment->validateTotalNotExceeded();
        });

        static::updating(function ($payment) {
            $payment->validateAmount();
            $payment->validateTotalNotExceeded($payment->id);
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function validateAmount(): void
    {
        if ($this->amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'L\'importo del pagamento deve essere maggiore di zero.'
            ]);
        }
    }

    protected function validateTotalNotExceeded(?int $ignorePaymentId = null): void
    {
        $invoice = $this->invoice ?: Invoice::find($this->invoice_id);

        $existingPayments = $invoice->payments();
        if ($ignorePaymentId) {
            $existingPayments->where('id', '!=', $ignorePaymentId);
        }

        $totalPaid = $existingPayments->sum('amount') + $this->amount;

        if ($totalPaid > $invoice->total) {
            throw ValidationException::withMessages([
                'amount' => 'Il totale dei pagamenti non pu\' superare l\'importo totale della fattura.'
            ]);
        }
    }
}
