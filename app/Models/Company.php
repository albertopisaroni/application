<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ApiToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


use App\Models\InvoiceNumbering;

class Company extends Model
{

    protected $fillable = [
        'openapi_id',
        'callbacks',
        'name',
        'legal_name',
        'piva',
        'forfettario',
        'regime_fiscale',
        'tax_code',
        'pec_email',
        'sdi_code',
        'legal_zip',
        'legal_city',
        'legal_province',
        'legal_country',
        'legal_street',
        'legal_number',
        'email',
        'phone',
        'website',
        'logo_path',
        'primary_color',
        'secondary_color',
        'short_description',
        'long_description',
        'subscription_plan_id',
        'subscription_renewal_date',
        'subscription_expiration_date',
        'subscription_status',
        'notes',
        'codice_fiscale',
        'rea_ufficio',
        'rea_numero',
        'rea_stato_liquidazione',
    ];

    private static function configureCallbacks(Company $company)
    {
        $fiscalId = $company->piva;
        $authToken = config('services.openapi.sdi.callback.token');

        $events = [
            'supplier-invoice',
            'customer-invoice',
            'invoice-status-quarantena',
            'invoice-status-invoice-error',
            'customer-notification',
            'legal-storage-missing-vat',
            'legal-storage-receipt',
        ];

        $callbacks = collect($events)->map(function ($event) use ($authToken) {
            return [
                'event'       => $event,
                'url'         => route('openapi.sdi.callback'),
                'auth_header' => 'Bearer ' . $authToken,
            ];
        })->values()->toArray();

        $payload = [
            'fiscal_id' => $fiscalId,
            'callbacks' => $callbacks,
        ];

        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openapi.sdi.token'),
            'Content-Type'  => 'application/json',
        ])->post(config('services.openapi.sdi.url') . '/api_configurations', $payload);

    
        if ($resp->successful()) {
            Log::info('📡 Callback SDI configurati', [
                'fiscal_id' => $fiscalId,
                'payload'   => $payload,
                'response'  => $resp->body(),
                'status'    => $resp->status(),
            ]);    
            $company->update(['callbacks' => 1]);
        } else {
            Log::error('❌ Errore configurazione callback SDI', [
                'fiscal_id' => $fiscalId,
                'error'     => $resp->body(),
            ]);
        }
    }

    public function getLogoAttribute(): string
    {
        // Se c'è un logo su S3
        if ($this->logo_path && Storage::disk('s3')->exists($this->logo_path)) {
            return Storage::disk('s3')->temporaryUrl(
                $this->logo_path,
                now()->addMinutes(5)
            );
        }

        // Fallback totale
        return 'https://ui-avatars.com/api/?format=svg&name=' . urlencode($this->name);
    }

    protected static function booted()
    {
        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });
    
        static::created(function ($company) {

            $json = [
                'fiscal_id'           => $company->piva,
                'name'                => $company->legal_name,
                'email'               => $company->slug . '@clienti.newo.io',
                'apply_signature'     => true,
                'apply_legal_storage' => true,
            ];
            
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openapi.sdi.token'),
                'Content-Type'  => 'application/json',
            ])->post(config('services.openapi.sdi.url') . '/business_registry_configurations', $json);
            
            $log = [
                'uuid'     => $company->uuid,
                'payload'  => $json,
                'response' => $resp->body(),
                'status'   => $resp->status(),
            ];
            
            if ($resp->successful() && $resp->json('data.id')) {
                $company->update(['openapi_id' => $resp->json('data.id')]);
                Log::info('✅ Registrazione SDI riuscita', $log);
            } else {

                // Fallback: verifica se l'azienda esiste già
                $resp2 = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.openapi.sdi.token'),
                    'Content-Type'  => 'application/json',
                ])->get(config('services.openapi.sdi.url') . '/business_registry_configurations/' . $company->piva);
            
                $log['response'] = $resp2->body();
                $log['status'] = $resp2->status();
            
                if ($resp2->successful() && $resp2->json('data.id')) {
                    $company->update(['openapi_id' => $resp2->json('data.id')]);
                    Log::info('✅ Recuperato openapi_id esistente da SDI', $log);
                } else {
                    Log::error('❌ Registrazione SDI fallita anche dopo fallback', $log);
                }

            }

            InvoiceNumbering::create([
                'company_id'     => $company->id,
                'type'           => 'standard',
                'prefix'         => null,
                'current_number' => 0,
                'name'           => 'Standard',
                'template_id'    => 1,
            ]);

        });

        static::saved(function ($company) {
            if (! $company->callbacks) {
                self::configureCallbacks($company);
            }
        });
    
        static::updating(function ($company) {
            if ($company->isDirty('name') && empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });
    } 

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('permissions')->withTimestamps();
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function clients()
    {
        return $this->hasMany(\App\Models\Client::class);
    }

    public function invoiceNumberings()
    {
        return $this->hasMany(\App\Models\InvoiceNumbering::class);
    }
    
    public function paymentMethods()
    {
        return $this->hasMany(\App\Models\PaymentMethod::class);
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function emailAccounts()
    {
        return $this->hasMany(EmailAccount::class);
    }

    public function stripeAccounts()
    {
        return $this->hasMany(StripeAccount::class);
    }

    public function defaultStripeAccount()
    {
        return $this->hasOne(StripeAccount::class)->where('default', true);
    }
}