<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'scribe.index')->name('scribe');

Route::get("postman", function () {

    return new JsonResponse(
        Storage::disk('local')->get('scribe/collection.json'),
        json: true
    );
})->name('scribe.postman');