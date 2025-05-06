<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'surname',
        'email',
        'hidden',
        'phone',
        'role',
        'receives_invoice_copy',
        'is_main_contact',
        'receives_notifications',
        'primary',
    ];

    protected $casts = [
        'primary' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($contact) {
            // Se è il primo contatto per quel client o non c'è nessun primary, impostalo come primary
            $hasPrimary = self::where('client_id', $contact->client_id)
                ->where('primary', true)
                ->exists();

            if (!$hasPrimary) {
                $contact->primary = true;
            }
        });

        static::saved(function ($contact) {
            // Se questo contatto è stato salvato come primary
            if ($contact->primary) {
                // Imposta tutti gli altri dello stesso client come non primary
                self::where('client_id', $contact->client_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['primary' => false]);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}