<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends Model
{
    protected $fillable = [
        'company_id',
        'imap_host',
        'imap_port',
        'imap_username',
        'imap_password',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function setImapPasswordAttribute($value)
    {
        $this->attributes['imap_password'] = Crypt::encryptString($value);
    }

    public function getImapPasswordAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    public function setSmtpPasswordAttribute($value)
    {
        $this->attributes['smtp_password'] = Crypt::encryptString($value);
    }

    public function getSmtpPasswordAttribute($value)
    {
        return Crypt::decryptString($value);
    }
}