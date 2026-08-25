<?php

namespace Jtif\SsoJtif;

use Illuminate\Support\ServiceProvider;
use Jtif\SsoJtif\Console\SsoInstallCommand;

class SsoJtifServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap layanan package secara otomatis.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->mergeConfigFrom(__DIR__.'/../config/sso-jtif.php', 'sso-jtif');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SsoInstallCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/sso-jtif.php' => config_path('sso-jtif.php'),
            ], 'sso-config');
        }
    }

    /**
     * Daftarkan binding kontainer.
     */
    public function register(): void
    {
        // Daftarkan SsoManager ke container dengan key 'oauth-informatika'
        $this->app->singleton('oauth-informatika', function ($app) {
            return new SsoManager();
        });
    }
}