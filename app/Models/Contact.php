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
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

