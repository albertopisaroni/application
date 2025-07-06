<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasProfilePhoto, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_company_id',
        'current_email_account_id',
        'invitation_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
        'current_company',       // appendiamo l'accessor
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class)
                    ->withPivot('permissions')
                    ->withTimestamps();
    }

    /**
     * Accessor per la company corrente.
     * Assicurati di eager-load le companies prima di usarlo!
     */
    public function getCurrentCompanyAttribute(): ?Company
    {
        // usa la collection già caricata, senza query aggiuntive
        return $this->companies
                    ->firstWhere('id', $this->current_company_id);
    }

    public function currentEmailAccount()
    {
        return $this->hasOne(EmailAccount::class)
                    ->where('id', $this->current_email_account_id);
    }
}