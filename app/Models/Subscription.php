<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'client_id',
        'stripe_subscription_id',
        'price_id',
        'status',
        'start_date',
        'current_period_end',
        'company_id',
        'stripe_account_id',
        'quantity',
        'unit_amount',
        'subtotal_amount',
        'discount_amount',
        'final_amount',
    ];

    protected $casts = [
        'start_date'      => 'datetime',
        'current_period_end' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function price()
    {
        return $this->belongsTo(Price::class);
    }

    public function stripeAccount()
    {
        return $this->belongsTo(StripeAccount::class);
    }

    public function getNettoPostTaxAttribute(): float
    {
        $company           = $this->client->company;
        $totale            = $this->final_amount/100;
        
        if ($totale <= 0) return 0.00;
    
        $anno              = $this->start_date->year;
        $coeff             = $company->coefficiente / 100;
        $aliquotaImposta   = $company->startup ? 0.05 : 0.15;
    
        // imponibile forfettario
        $imponibile        = round($totale * $coeff, 2);
    
        // bollo
        $bollo             = $totale > 77 ? 2 : 0;
    
        // fatturato annuo (e fallback se zero)
        $fatturatoAnnuale  = Invoice::where('company_id', $company->id)
                                  ->whereYear('issue_date', $anno)
                                  ->sum('total');
        $fatturatoAnnuale  = $fatturatoAnnuale > 0
                          ? $fatturatoAnnuale
                          : $totale;
    
        // quota fissa proporzionale
        $contributiFissi   = $company->gestione_separata ? 0 : 4200;
        $quotaFissa        = round(($totale / $fatturatoAnnuale) * $contributiFissi, 2);
    
        if ($company->gestione_separata) {
            // gestione separata
            $inpsPercentuale = round($imponibile * 0.2607, 2);
            $inps            = $inpsPercentuale;
            $imposta         = round($imponibile * $aliquotaImposta, 2);
        } else {
            // artigiani / commercianti
            $inpsPercentuale = round($imponibile * 0.24, 2);
            $inps            = $inpsPercentuale + $quotaFissa;
            // IRPEF sul solo imponibile netto INPS%
            $impostaBase     = $imponibile - $inpsPercentuale;
            $imposta         = round($impostaBase * $aliquotaImposta, 2);
        }
    
        // calcolo netto
        $netto = $totale
               - $inps
               - $imposta
               - $bollo;
    
        return round($netto, 2);
    }
}