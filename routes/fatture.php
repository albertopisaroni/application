<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\FattureController;

Route::get('/', fn () => redirect(config('app.app_url').'/fatture'));

Route::get('/{uuid}/pdf', [FattureController::class, 'fatturePdf'])->whereUuid('uuid')->name('fatture.pdf');