<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'company_id',
        'numbering_id',
        'payment_method_id',
        'issue_date',
        'due_date',
        'fiscal_year',
        'invoice_number',
        'invoice_date',
        'client_name',
        'client_address',
        'client_email',
        'client_phone',
        'items',
        'subtotal',
        'pdf_path',
        'vat',
        'total',
    ];

    // Se gli items sono in JSON, puoi farli castare come array:
    protected $casts = [
        'items' => 'array',
        'issue_date' => 'date',
    ];

    // Relazione con la società (se necessario)
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}