<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Mail\CompanyInvite;
use Illuminate\Support\Facades\Mail;
use App\Models\Company;
use App\Models\ApiToken;
use App\Models\Invitation;

class AuthController extends Controller
{
    public function showInvitationForm($token)
    {
        $user = User::where('invitation_token', $token)->firstOrFail();
        return view('auth.invite', compact('user'));
    }

    public function acceptInvitation(Request $request, $token)
    {
        $user = User::where('invitation_token', $token)->firstOrFail();

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'invitation_token' => null,
        ]);

        auth()->login($user);

        return redirect('/dashboard');
    }
}
