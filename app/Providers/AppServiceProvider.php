<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Contracts\Events\Dispatcher;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

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

        // 🔐 API Routes
        Route::middleware(['api', ApiTokenMiddleware::class])
            ->domain($apiDomain)
            ->group(base_path('routes/api.php'));

        // 📄 Fatture Routes
        Route::domain($fatturaDomain)
            ->group(base_path('routes/fatture.php'));

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