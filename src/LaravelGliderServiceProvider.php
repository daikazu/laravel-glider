<?php

namespace Daikazu\LaravelGlider;

use Daikazu\LaravelGlider\Console\Commands\ClearGliderCache;
use Daikazu\LaravelGlider\Imaging\GlideServer;
use Daikazu\LaravelGlider\View\Components\Figure;
use Daikazu\LaravelGlider\View\Components\Picture;
use Daikazu\LaravelGlider\View\Components\Img;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use League\Glide\Server;

class LaravelGliderServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'glider');

        $this->loadViewComponentsAs('glider', [
            Img::class,
            Picture::class,
        ]);


        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');

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
        $this->mergeConfigFrom(__DIR__.'/../config/glider.php', 'glider');
        // Register the service the package provides.

        $this->app->singleton(Server::class, function ($app) {
            return GlideServer::create();
        });

        $this->app->singleton('laravel-glider', function ($app) {
            return new Glider();
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravel-glider', Server::class];
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
            __DIR__.'/../config/glider.php' => config_path('glider.php'),
        ], 'glider-config');

        // Publishing the views.
        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/glider'),
        ], 'glider-views');


        // Registering package commands.
         $this->commands([ClearGliderCache::class]);
    }
}
