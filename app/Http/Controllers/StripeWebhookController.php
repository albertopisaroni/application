<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\StripeAccount;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Price;
use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;

class StripeWebhookController extends Controller
{
    /**
     * Gestisce i webhook di Stripe per aggiornamenti in tempo reale
     */
    public function handle(Request $request)
    {

        Log::info('Stripe webhook received', ['request' => $request->all()]);
       
    }
} 