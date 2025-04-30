<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{

    protected $fillable = [
        'company_id',
        'type',
        'iban',
        'name',
        'sdi_code',
    ];
   
}