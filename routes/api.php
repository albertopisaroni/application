<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiTokenMiddleware;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\StripeConnectController;
use App\Models\Registration;
use Illuminate\Support\Facades\Log;
use App\Services\LeadAnalyzer;

/**
 * Questo endpoint non verrà documentato.
 *
 * @hideFromAPIDocumentation
 */
Route::get('/', fn () => redirect('https://docs.newopay.it'))->withoutMiddleware(ApiTokenMiddleware::class);

/**
 * Questo endpoint non verrà documentato.
 *
 * @hideFromAPIDocumentation
 */
Route::get('/stripe/connect/callback', [StripeConnectController::class, 'callback'])->withoutMiddleware(ApiTokenMiddleware::class)->name('stripe.connect.callback');



/**
 * Questo endpoint non verrà documentato.
 *
 * @hideFromAPIDocumentation
 */
Route::post('/public/form/lead', function (Request $request) {
    
    Log::info('Lead creation request', ['request' => $request->all()]);

    app()->setLocale('it');

    $validator = Validator::make($request->all(), [
        'fullname' => 'required|string|max:100',
        'phone' => 'required|string|max:20|min:7',
        'email' => 'required|email|max:100',
        'location' => 'nullable|string|max:100',
        'label' => 'nullable|string|max:100',
        'utm_source' => 'nullable|string|max:100',
        'utm_medium' => 'nullable|string|max:100',
        'utm_campaign' => 'nullable|string|max:100',
        'utm_content' => 'nullable|string|max:100',
        'ab_variant' => 'nullable|string|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
    }

    $trackingFields = collect($request->all())
        ->filter(fn($v, $k) => str_starts_with($k, 'section_time'))
        ->mapWithKeys(fn($v, $k) => [str_replace(['-', '–'], '_', $k) => $v]);

    $leadData = array_merge(
        $validator->validated(),
        $request->only([
            'page_time', 'scroll_time', 'scroll_bounce', 'mouse_movement',
            'form_time_fullname', 'form_time_email', 'form_time_phone',
            'form_autofill_fullname', 'form_autofill_email', 'form_autofill_phone'
        ]),
        $trackingFields->toArray()
    );

    $profileData = LeadAnalyzer::analyze($request->all());
    $leadData['behavior_profile'] = $profileData['profile'];
    $leadData['behavior_score'] = $profileData['score'];

    $lead = Registration::create($leadData);

    return response()->json(['status' => 'ok', 'lead' => $lead->only(['uuid'])]);

})->withoutMiddleware(\App\Http\Middleware\ApiTokenMiddleware::class);


/**
 * Questo endpoint non verrà documentato.
 *
 * @hideFromAPIDocumentation
 */
Route::put('/public/form/lead/{uuid}', function (Request $request, $uuid) {
    
    Log::info('Lead update request', ['uuid' => $uuid, 'request' => $request->all()]);

    app()->setLocale('it');

    $lead = Registration::where('uuid', $uuid)->first();
    if (! $lead) {
        return response()->json(['status' => 'error', 'message' => 'Lead non trovato.'], 404);
    }

    $validator = Validator::make($request->all(), [
        'project_type' => 'required|string|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
    }

    $trackingFields = collect($request->all())
        ->filter(fn($v, $k) => str_starts_with($k, 'section_time'))
        ->mapWithKeys(fn($v, $k) => [str_replace(['-', '–'], '_', $k) => $v]);

    $leadData = array_merge(
        $validator->validated(),
        $request->only([
            'page_time', 'scroll_time', 'scroll_bounce', 'mouse_movement',
            'form_time_fullname', 'form_time_email', 'form_time_phone',
            'form_autofill_fullname', 'form_autofill_email', 'form_autofill_phone'
        ]),
        $trackingFields->toArray()
    );

    $profileData = LeadAnalyzer::analyze($request->all());
    $leadData['behavior_profile'] = $profileData['profile'];
    $leadData['behavior_score'] = $profileData['score'];

    $lead->update($leadData);

    return response()->json(['status' => 'ok', 'lead' => $lead->only(['uuid'])]);

})->withoutMiddleware(\App\Http\Middleware\ApiTokenMiddleware::class);



Route::prefix('clients')->group(function () {
    Route::post('/store/manual', [ClientController::class, 'storeManual']);
    Route::post('/store/automatic', [ClientController::class, 'storeAutomatic']);
});

