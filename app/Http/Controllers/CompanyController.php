<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ApiToken;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\CompanyInvite;
use App\Mail\CompanyAdded;
use Illuminate\Support\Facades\Storage;




class CompanyController extends Controller
{
    public function create()
    {
        return view('company.create');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);

        $companyId = auth()->user()->current_company_id;
        $company = Company::findOrFail($companyId);

        $file = $request->file('logo');
        $filename = 'logo.' . $file->getClientOriginalExtension();
        $path = "clienti/{$company->slug}/loghi/{$filename}";

        Storage::disk('s3')->put($path, file_get_contents($file));

        $company->update(['logo_path' => $path]);

        return redirect()->back()->with('success', 'Logo aggiornato con successo.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'tax_code' => 'required|string|max:50|unique:companies,tax_code',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birth_date' => 'required|date',
            'birth_city' => 'required|string|max:100',
            'birth_province' => 'required|string|max:50',
            'nationality' => 'required|string|max:50',
            'residence_address' => 'required|string|max:255',
            'residence_zip' => 'required|string|max:20',
            'residence_city' => 'required|string|max:100',
            'residence_province' => 'required|string|max:50',
            'residence_country' => 'required|string|max:50',
            'personal_email' => 'required|email|max:255',
            'personal_phone' => 'required|string|max:30',
            'business_description' => 'required|string|min:10',
        ]);

        // Qui potrai in futuro richiamare GPT per ATECO suggestion

        $company = Company::create([
            'name' => $validated['name'],
            'legal_name' => $validated['legal_name'] ?? null,
            'tax_code' => $validated['tax_code'],
            'slug' => Str::slug($validated['name']),
            // altri campi verranno salvati nel profilo utente o in tabella parallela
        ]);

        $request->user()->companies()->attach($company->id);
        session(['current_company_id' => $company->id]);
        $request->user()->update(['current_company_id' => $company->id]);

        return redirect()->route('dashboard');
    }

    public function import()
    {
        return view('company.import');
    }

    public function importSubmit(Request $request)
    {
        $request->validate([
            'piva' => 'required|string|exists:companies,piva',
        ]);

        $company = Company::where('piva', $request->piva)->first();

        if (! $company) {
            return back()->withErrors(['piva' => 'Nessuna società trovata con questa P.IVA.']);
        }

        $request->user()->companies()->syncWithoutDetaching([$company->id]);
        session(['current_company_id' => $company->id]);
        $request->user()->update(['current_company_id' => $company->id]);

        return redirect()->route('dashboard');
    }




    public function show()
    {
        $company = Auth::user()->companies()
            ->with(['users', 'apiTokens'])
            ->findOrFail(session('current_company_id'));

        $existingUserIds = $company->users()->pluck('user_id');

        $allUsers = User::whereNotIn('id', $existingUserIds)->get();

        return view('company.show', [
            'company' => $company,
            'users' => $company->users,
            'tokens' => $company->apiTokens,
            'allUsers' => $allUsers,
        ]);
    }

    public function storeToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expires_in' => 'required',
        ]);

        $company = Auth::user()->companies()->firstOrFail();

        $plainToken = Str::random(60);

        $expiresAt = $request->expires_in === 'never'
            ? null
            : now()->addDays((int) $request->expires_in);

        $token = ApiToken::create([
            'company_id' => $company->id,
            'name' => $request->name,
            'token' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
        ]);

        session()->flash('token', $plainToken); // mostra solo ora

        return back()->with('new_token', $plainToken);
    }

    public function deleteToken($id)
    {
        $company = Auth::user()->companies()->firstOrFail();

        $token = ApiToken::where('company_id', $company->id)->findOrFail($id);
        $token->delete();

        return back()->with('success', 'Token eliminato con successo.');
    }

    public function addUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        $company = auth()->user()->currentCompany;

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Nuovo utente: crea e invia email con link di invito
            $passwordToken = Str::random(60);

            $user = User::create([
                'name' => $request->name,
                'surname' => $request->surname,
                'email' => $request->email,
                'password' => Hash::make(Str::random(16)), // temporaneo
                'invitation_token' => $passwordToken,
            ]);

            Mail::to($user->email)->send(new \App\Mail\CompanyInvite($company, $user));
        } else {
            // Utente esistente: notifica
            Mail::to($user->email)->send(new \App\Mail\CompanyAdded($company));
        }

        $company->users()->syncWithoutDetaching([$user->id]);

        return back()->with('success', 'Utente associato con successo.');
    }



    public function removeUser($userId)
    {
        $company = auth()->user()->currentCompany;
    
        // Impedisci di rimuovere se stessi
        if (auth()->id() == $userId) {
            return back()->with('error', 'Non puoi rimuovere te stesso dalla company.');
        }
    
        $company->users()->detach($userId);
    
        return back()->with('success', 'Utente rimosso dalla company.');
    }

    public function renewToken($id)
    {
        $token = ApiToken::findOrFail($id);
        $token->expires_at = now()->addDays(30); // default rinnovato di 30 giorni
        $token->save();

        return back()->with('success', 'Token rinnovato di 30 giorni.');
    }
    
}