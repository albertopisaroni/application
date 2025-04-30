<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\FattureController;


Route::get('/', fn () => redirect(config('app.app_url')));

Route::get('/{uuid?}', function ($uuid = null) { return view('onboarding', ['uuid' => $uuid]); })->name('guest.onboarding');