<?php

namespace CarlVallory\KrayinGoogleAuth\Providers;

use Illuminate\Support\ServiceProvider;

class KrayinGoogleAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/google-auth.php', 'google-auth');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/routes.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'google-auth');

        // Empuja las credenciales a services.google para Socialite sin editar config/services.php
        config(['services.google' => config('google-auth.credentials')]);

        // Inyecta el botón de Google en el login sin tocar el Blade del core.
        \Illuminate\Support\Facades\Event::listen(
            'admin.sessions.login.form_controls.before',
            function ($viewRenderEventManager) {
                $viewRenderEventManager->addTemplate('google-auth::google-button');
            }
        );
    }
}
