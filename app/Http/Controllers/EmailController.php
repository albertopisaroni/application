<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function list(Request $request)
    {
        $company = currentCompany();
        $account = currentEmailAccount();

        abort_if(!$company || !$account, 403);
        abort_if($account->company_id !== $company->id, 403); // doppia sicurezza

        return view('emails.index', compact('account'));
    }
}