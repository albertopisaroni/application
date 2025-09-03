<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscoapiSession extends Model
{
    protected $fillable = [
        'user_id',
        'id_sessione',
        'stato',
        'ente',
        'tipo_login',
        'qr_code',
        'refresh_token',
        'response',
        'post_login_executed',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
