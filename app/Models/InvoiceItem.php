<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'name',
        'description',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'discount',
        'vat_rate',
    ];
}
