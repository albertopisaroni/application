<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePaymentSchedule extends Model
{
    protected $fillable = ['invoice_id','due_date','amount','type','percent'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}