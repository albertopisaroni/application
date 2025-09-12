<?php

use Illuminate\Support\Facades\Route;

use App\Http\Middleware\EnsureUserHasCompany;
use App\Http\Middleware\IsAdmin;


use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AbbonamentiController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OpenBankingController;
use App\Http\Controllers\SpeseController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\F24Controller;




use App\Livewire\App\Dashboard;
use App\Livewire\App\TaxCalculator;



Route::get('/stripe/connect', [StripeConnectController::class, 'redirect'])->name('stripe.connect');


Route::get('/qr/{uuid}', [QrController::class, 'show'])->name('qr.show');

Route::get('/upload-document/{uuid}', function ($uuid) {
    return view('app.registration.upload-mobile', ['uuid' => $uuid]);
})->name('registration.upload.mobile');




// Social login
Route::get('social/{provider}/redirect', [SocialController::class, 'redirect'])->name('social.redirect');
Route::get('social/{provider}/callback', [SocialController::class, 'callback'])->name('social.callback');
Route::post('social/{provider}/callback', [SocialController::class, 'callbackPost'])->name('social.callback');



Route::get('/invito/{token}', [AuthController::class, 'showInvitationForm'])->name('invitation.show');
Route::post('/invito/{token}', [AuthController::class, 'acceptInvitation'])->name('invitation.accept');


Route::middleware([ 'auth:sanctum', config('jetstream.auth_session'), 'verified' ])->group(function () {

    Route::get('/onboarding/company', [OnboardingController::class, 'index'])->name('onboarding.company');

    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');

    Route::get('/companies/import', [CompanyController::class, 'import'])->name('companies.import');
    Route::post('/companies/import', [CompanyController::class, 'importSubmit'])->name('companies.import.submit');






    Route::middleware([EnsureUserHasCompany::class])->group(function () {

        
        Route::get('/', Dashboard::class)->name('dashboard');

        Route::get('/tasse', TaxCalculator::class)->name('tasse.calculator');

        Route::get('/abbonamenti', [AbbonamentiController::class, 'lista'])->name('abbonamenti.lista');

        // FATTURE
        Route::get('/fatture', [InvoiceController::class, 'list'])->name('fatture.lista');
        Route::get('/fatture/nuova', [InvoiceController::class, 'create'])->name('fatture.nuova');
        Route::post('/fatture', [InvoiceController::class, 'store'])->name('fatture.store');

        // FATTURE RICORRENTI
        Route::get('/fatture-ricorrenti', [App\Http\Controllers\RecurringInvoiceController::class, 'index'])->name('fatture-ricorrenti.lista');
        Route::get('/fatture-ricorrenti/nuova', [App\Http\Controllers\RecurringInvoiceController::class, 'create'])->name('fatture-ricorrenti.nuova');
        Route::post('/fatture-ricorrenti', [App\Http\Controllers\RecurringInvoiceController::class, 'store'])->name('fatture-ricorrenti.store');
        Route::get('/fatture-ricorrenti/{recurringInvoice}', [App\Http\Controllers\RecurringInvoiceController::class, 'show'])->name('fatture-ricorrenti.show');
        Route::get('/fatture-ricorrenti/{recurringInvoice}/edit', [App\Http\Controllers\RecurringInvoiceController::class, 'edit'])->name('fatture-ricorrenti.edit');
        Route::put('/fatture-ricorrenti/{recurringInvoice}', [App\Http\Controllers\RecurringInvoiceController::class, 'update'])->name('fatture-ricorrenti.update');
        Route::delete('/fatture-ricorrenti/{recurringInvoice}', [App\Http\Controllers\RecurringInvoiceController::class, 'destroy'])->name('fatture-ricorrenti.destroy');
        Route::patch('/fatture-ricorrenti/{recurringInvoice}/toggle-active', [App\Http\Controllers\RecurringInvoiceController::class, 'toggleActive'])->name('fatture-ricorrenti.toggle-active');
        Route::get('/ajax/clients/{client}/subscriptions', [App\Http\Controllers\RecurringInvoiceController::class, 'getClientSubscriptions'])->name('ajax.clients.subscriptions');

        // NOTE DI CREDITO
        Route::get('/note-di-credito', [InvoiceController::class, 'creditNotesList'])->name('note-di-credito.lista');
        Route::get('/note-di-credito/nuova', [InvoiceController::class, 'createCreditNote'])->name('note-di-credito.nuova');
        Route::post('/note-di-credito', [InvoiceController::class, 'storeCreditNote'])->name('note-di-credito.store');

        // AUTOFATTURE
        Route::get('/autofatture', [InvoiceController::class, 'selfInvoicesList'])->name('autofatture.lista');
        Route::get('/autofatture/nuova', [InvoiceController::class, 'createSelfInvoice'])->name('autofatture.nuova');
        Route::post('/autofatture', [InvoiceController::class, 'storeSelfInvoice'])->name('autofatture.store');


        // SPESE
        Route::get('/spese', [SpeseController::class, 'list'])->name('spese.lista');
        Route::get('/tasse', [TaxController::class, 'list'])->name('tasse.lista');

        // F24
        Route::get('/f24', [F24Controller::class, 'index'])->name('f24.index');
        Route::get('/f24/{f24}', [F24Controller::class, 'show'])->name('f24.show');
        Route::get('/f24/{f24}/download', [F24Controller::class, 'download'])->name('f24.download');
        Route::post('/f24/{f24}/mark-as-paid', [F24Controller::class, 'markAsPaid'])->name('f24.mark-as-paid');
        Route::delete('/f24/{f24}', [F24Controller::class, 'destroy'])->name('f24.destroy');
        
        // F24 Ricevute
        Route::post('/f24/{f24}/receipt', [F24Controller::class, 'uploadReceipt'])->name('f24.upload-receipt');
        Route::get('/f24/{f24}/receipt', [F24Controller::class, 'downloadReceipt'])->name('f24.download-receipt');
        Route::delete('/f24/{f24}/receipt', [F24Controller::class, 'deleteReceipt'])->name('f24.delete-receipt');
        
        // API F24
        Route::get('/api/f24', [F24Controller::class, 'apiIndex'])->name('f24.api.index');
        Route::get('/api/f24/{f24}', [F24Controller::class, 'apiShow'])->name('f24.api.show');

        Route::post('/update-company', function (Request $request) {
            session(['current_company_id' => $request->input('company_id')]);
            return response()->json(['status' => 'ok']);
        })->name('update.company');




         // Contatti
        // --------

        Route::middleware([IsAdmin::class])->prefix('admin')->group(function () {

            Route::get('/registrations', [AdminController::class, 'registrationIndex'])->name('admin.registrations.index');
            Route::get('/registrations/{registration}', [AdminController::class, 'show'])->name('admin.registrations.show');

        });





        // Azienda
        // -------

        Route::prefix('company')->group(function () {
            Route::get('/', [CompanyController::class, 'show'])->name('company.show');
            Route::post('/api-tokens', [CompanyController::class, 'storeToken'])->name('company.tokens.store');
            Route::delete('/api-tokens/{id}', [CompanyController::class, 'deleteToken'])->name('company.tokens.delete');
            Route::delete('/users/{user}', [CompanyController::class, 'removeUser'])->name('company.users.remove');
            Route::post('/users/add', [CompanyController::class, 'addUser'])->name('company.users.add');

            Route::delete('/tokens/{id}', [CompanyController::class, 'deleteToken'])->name('company.tokens.delete');
            Route::post('/tokens/{id}/renew', [CompanyController::class, 'renewToken'])->name('company.tokens.renew');

            Route::post('/logo', [CompanyController::class, 'uploadLogo'])->name('company.logo.upload');
        });



        // Email
        // --------

        Route::prefix('email')->group(function () {

            Route::get('/', [EmailController::class, 'list'])->name('email.list');

        });



        // Contatti
        // --------

        Route::prefix('contatti')->group(function () {


            // Lista clienti
            Route::prefix('clienti')->group(function () {


                // Lista utenti
                Route::prefix('utenti')->group(function () {

                    Route::post('/{client}', [ContactController::class, 'clientStore'])->name('contatti.clienti.contact.store');
                    Route::delete('/{contact}', [ContactController::class, 'clientDestroy'])->name('contatti.clienti.contact.destroy');
                    Route::get('/{contact}/edit', [ContactController::class, 'clientEdit'])->name('contatti.clienti.contact.edit');
                    Route::put('/{contact}', [ContactController::class, 'clientUpdate'])->name('contatti.clienti.contact.update');

                });


                // Rotte per i clienti
                Route::get('/', [ContactController::class, 'index'])->name('contatti.clienti.lista'); // Lista clienti
                Route::get('/create', [ContactController::class, 'create'])->name('contatti.clienti.nuovo'); // Aggiungi cliente
                Route::put('/{client}/hide', [ContactController::class, 'hide'])->name('contatti.clienti.hide');
                Route::post('/', [ContactController::class, 'store'])->name('contatti.clienti.store'); // Salva cliente
                Route::get('/{client}', [ContactController::class, 'show'])->name('contatti.clienti.show'); // Visualizza cliente e contatti
                Route::get('/{client}/edit', [ContactController::class, 'edit'])->name('contatti.clienti.edit'); // Modifica cliente
                Route::put('/{client}', [ContactController::class, 'update'])->name('contatti.clienti.update'); // Aggiorna cliente
                Route::get('/create/lookup', [ContactController::class, 'createLookup'])->name('contatti.clienti.nuovo.lookup'); // Aggiungi cliente
                Route::post('/create/lookup', [ContactController::class, 'createLookupPost']);



            });







        });





    });





});
