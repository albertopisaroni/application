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
        // 🔐 API Routes
        Route::middleware(['api', ApiTokenMiddleware::class])
            ->domain('api.newopay.it')
            ->group(base_path('routes/api.php'));

        // 📄 Docs Routes
        Route::domain('docs.newopay.it')
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