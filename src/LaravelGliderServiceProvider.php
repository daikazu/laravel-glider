<?php

namespace Daikazu\LaravelGlider;

use Illuminate\Support\ServiceProvider;

class LaravelGliderServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'daikazu');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'daikazu');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-glider.php', 'laravel-glider');

        // Register the service the package provides.
        $this->app->singleton('laravel-glider', function ($app) {
            return new LaravelGlider;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravel-glider'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/laravel-glider.php' => config_path('laravel-glider.php'),
        ], 'laravel-glider.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/daikazu'),
        ], 'laravel-glider.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/daikazu'),
        ], 'laravel-glider.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/daikazu'),
        ], 'laravel-glider.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
