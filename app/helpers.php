<?php

use App\Models\Company;
use App\Models\EmailAccount;
use Illuminate\Support\Str;

if (!function_exists('currentCompany')) {
    function currentCompany(): ?Company
    {
        if (session()->has('current_company_id')) {
            return Company::find(session('current_company_id'));
        }

        if (auth()->check() && auth()->user()->current_company_id) {
            return Company::find(auth()->user()->current_company_id);
        }

        return null;
    }
}

if (!function_exists('currentEmailAccount')) {
    function currentEmailAccount(): ?EmailAccount
    {
        if (session()->has('current_email_account_id')) {
            return EmailAccount::find(session('current_email_account_id'));
        }

        if (auth()->check() && auth()->user()->current_email_account_id) {
            return EmailAccount::find(auth()->user()->current_email_account_id);
        }

        if (currentCompany()) {
            return currentCompany()->emailAccounts()->first();
        }

        return null;
    }
}

if (!function_exists('companyUrl')) {
    function companyUrl(string $path = ''): string
    {
        $company = currentCompany();
        $slug = $company ? $company->slug : 'azienda';

        return url("/azienda/{$slug}/" . ltrim($path, '/'));
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount, $currency = '€', $decimals = 2): string
    {
        return $currency . ' ' . number_format($amount, $decimals, ',', '.');
    }
}

if (!function_exists('companyName')) {
    function companyName(): string
    {
        $company = currentCompany();
        return $company ? $company->name : 'Nessuna azienda';
    }
}

if (!function_exists('truncate')) {
    function truncate(string $text, int $limit = 50): string
    {
        return Str::limit($text, $limit);
    }
}