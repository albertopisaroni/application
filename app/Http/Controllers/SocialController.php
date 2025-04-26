<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\Registration;
use App\Models\User;

class SocialController extends Controller
{
    public function redirect(Request $request, $provider)
    {
        session(['social_auth_origin' => $request->query('origin', 'login')]);

        return Socialite::driver($provider)
            ->redirectUrl(route('social.callback', $provider))
            ->redirect();
    }

    public function callback(Request $request, $provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();
        
        $origin = session('social_auth_origin', 'login');

        if($origin == 'start') {
            
            $name = $socialUser->user['given_name'] ?? $socialUser->user['givenName'] ?? $socialUser->getName();
            $surname = $socialUser->user['family_name'] ?? $socialUser->user['surname'] ?? '';
            
            
            // dd($name, $surname);

            $registration = Registration::firstOrCreate(
                ['email' => $email], // solo chiave di ricerca!
                [
                    'uuid' => Str::uuid(),
                    'name' => $name,
                    'surname' => $surname,
                    'step' => 3,
                ]
            );

            return redirect()->route('guest.onboarding', ['uuid' => $registration->uuid]);

        }









        // // Crea o recupera l'utente
        // $user = User::firstOrCreate(
        //     ['email' => $socialUser->getEmail()],
        //     ['name' => $socialUser->getName()]
        // );

        // Auth::login($user);

        dd($socialUser);

        // Reindirizza in base all'origine
        return match ($origin) {
            'start' => redirect()->route('registration.step.2'),
            default => redirect()->route('dashboard'),
        };
    }


    public function callbackPost(Request $request)
    {
        $token = $request->input('credential');

        $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
        $payload = $client->verifyIdToken($token);

        if ($payload) {
            $email = $payload['email'];
            $name = $payload['given_name'];
            $surname = $payload['family_name'];

            $registration = Registration::firstOrCreate(
                ['email' => $email], // solo chiave di ricerca!
                [
                    'uuid' => Str::uuid(),
                    'name' => $name,
                    'surname' => $surname,
                    'step' => 3,
                ]
            );

            session(['uuid' => $registration->uuid]);

            return redirect()->route('guest.onboarding', ['uuid' => $registration->uuid]);
        }

        return response()->json(['error' => 'invalid_token'], 401);
    }

}
