<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Contracts\Events\Dispatcher;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

use App\Models\Contact;
use App\Observers\ContactObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $apiDomain = parse_url(config('app.api_url'), PHP_URL_HOST);
        $docsDomain = parse_url(config('app.docs_url'), PHP_URL_HOST);
        $fatturaDomain = parse_url(config('app.fatture_url'), PHP_URL_HOST);
        $benvenutoDomain = parse_url(config('app.benvenuto_url'), PHP_URL_HOST);

        Contact::observe(ContactObserver::class);

        // 🔐 API Routes
        Route::middleware(['api', ApiTokenMiddleware::class])
            ->domain($apiDomain)
            ->group(base_path('routes/api.php'));

        // 📄 Fatture Routes
        Route::middleware(['web'])
            ->domain($fatturaDomain)
            ->group(base_path('routes/fatture.php'));

        // 🏠 Benvenuto Route
        Route::middleware(['web'])
            ->domain($benvenutoDomain)
            ->group(base_path('routes/benvenuto.php'));

        // 📄 Docs Routes
        Route::domain($docsDomain)
            ->group(base_path('routes/docs.php'));

        // 🌐 Socialite: provider Microsoft
        if (class_exists(SocialiteWasCalled::class)) {
            app(Dispatcher::class)->listen(
                SocialiteWasCalled::class,
                MicrosoftExtendSocialite::class
            );
        }
    }
}