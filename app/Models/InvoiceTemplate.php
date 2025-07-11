<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTemplate extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'blade',
        'type'
    ];
}