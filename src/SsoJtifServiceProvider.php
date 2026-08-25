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
        // Memuat rute autentikasi SSO bawaan package
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Memuat berkas konfigurasi bawaan package
        $this->mergeConfigFrom(__DIR__.'/../config/sso-jtif.php', 'sso-jtif');

        // Mendaftarkan artisan command interaktif saat berjalan di konsol
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
     * Daftarkan binding kontainer jika diperlukan.
     */
    public function register(): void
    {
        // Pendaftaran layanan tambahan
    }
}