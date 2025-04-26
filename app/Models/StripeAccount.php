<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StripeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'stripe_user_id',
        'access_token',
        'refresh_token',
        'default',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}