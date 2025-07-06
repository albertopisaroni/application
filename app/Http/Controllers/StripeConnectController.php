<?php

// app/Http/Controllers/StripeConnectController.php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\StripeAccount;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

use Stripe\StripeClient;

class StripeConnectController extends Controller
{

    /**
     * Questo endpoint non verrà documentato.
     *
     * @hideFromAPIDocumentation
     */
    public function redirect()
    {

        $stripeAccount = StripeAccount::where('company_id', 1)->first();

        $stripe = new StripeClient($stripeAccount->access_token);

        // // Esempio: lista clienti
        // $customers = $stripe->customers->all();

        // // Esempio: lista prodotti
        // $products = $stripe->products->all();

        // Esempio: lista abbonamenti
        // $subscriptions = $stripe->subscriptions->all();
        // return json_encode($subscriptions);

        // oggetto vuoto
        // $oggetto = new \stdClass();
        // $oggetto->data = [];

        // $startingAfter = null;
        // do {
        //     $params = ['limit' => 100];
        //     if ($startingAfter) {
        //         $params['starting_after'] = $startingAfter;
        //     }

        //     $subscriptions = $stripe->subscriptions->all($params, [
        //         'stripe_account' => $stripeAccount->stripe_user_id,
        //     ]);

        //     foreach ($subscriptions->data as $subscription) {
                
        //         // aggiungi all'oggetto la subscription
        //         $oggetto->data[] = $subscription;

        //     }

        //     $startingAfter = count($subscriptions->data) ? end($subscriptions->data)->id : null;
        // } while ($subscriptions->has_more);


        // //stampa l'oggetto come json
        // return json_encode($oggetto);


        


        $companyId = auth()->user()->current_company_id;
        $company = Company::findOrFail($companyId);

        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.stripe.client_id'),
            'scope' => 'read_write',
            'redirect_uri' => config('services.stripe.redirect'),
            'state' => $company->id,
        ]);

        return redirect('https://connect.stripe.com/oauth/authorize?' . $query);
    }

    /**
     * Questo endpoint non verrà documentato.
     *
     * @hideFromAPIDocumentation
     */
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $companyId = $request->get('state'); // passato dallo step 1
        $company = Company::findOrFail($companyId);

        $response = Http::asForm()->post('https://connect.stripe.com/oauth/token', [
            'client_secret' => config('services.stripe.secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            return redirect(config('app.app_url').'/abbonamenti')->with('error', 'Errore nel collegamento Stripe');
        }

        $data = $response->json();

        $company->stripeAccounts()->create([
            'stripe_user_id' => $data['stripe_user_id'],
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'default' => $company->stripeAccounts()->count() === 0, // primo = default
        ]);

        $stripeAccount = StripeAccount::where('access_token', $data['access_token'])->first();

        if (! $stripeAccount) {
            return redirect(config('app.app_url').'/abbonamenti')->with('error', 'Errore nel collegamento Stripe: account non trovato');
        }

        Artisan::queue('stripe:sync', [
            'stripe_account_id' => $stripeAccount->id,
        ]);


        return redirect(config('app.app_url').'/abbonamenti')->with('success', 'Stripe collegato con successo');
    }
}