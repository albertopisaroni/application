<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;
use Illuminate\Events\Attributes\AsEventListener;

#[AsEventListener]
class SetCurrentCompanyAfterLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (!$user->current_company_id) {
            $defaultCompanyId = $user->companies()->latest('id')->first()?->id ?? $user->companies()->first()?->id;

            if ($defaultCompanyId) {
                $user->update(['current_company_id' => $defaultCompanyId]);
                Session::put('current_company_id', $defaultCompanyId);
            }
        } else {
            Session::put('current_company_id', $user->current_company_id);
        }
    }
}