<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider;

use Daikazu\LaravelGlider\Commands\ClearGlideCacheCommand;
use Daikazu\LaravelGlider\Components\Img;
use Daikazu\LaravelGlider\Components\ImgResponsive;
use Daikazu\LaravelGlider\Components\ResponsiveBackground;
use Daikazu\LaravelGlider\Facades\Glide;
use Daikazu\LaravelGlider\Factories\ResponseFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\UrlGenerator;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureInterface;
use League\Glide\Urls\UrlBuilder;
use League\Glide\Urls\UrlBuilderFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelGliderServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-glider')
            ->hasConfigFile()
            ->hasViews('laravel-glider')
            ->hasViewComponents('glide', Img::class, ImgResponsive::class, ResponsiveBackground::class)
            ->hasRoute('web')
            ->hasCommand(ClearGlideCacheCommand::class);
    }

    public function packageBooted(): void
    {

        // inject the glider link in the filesystem links config array while preserving the existing ones
        config(['filesystems.links' => array_merge(config('filesystems.links'), [public_path(config('glider.base_url')) => storage_path('app/public')])]);

        $this->app->singleton(Glide::class, GlideService::class);

        $this->app->instance(SignatureInterface::class, SignatureFactory::create((string) config('glider.sign_key', '')));

        $this->app->bind(UrlBuilder::class, fn (Application $app): UrlBuilder => UrlBuilderFactory::create(
            $app->make(UrlGenerator::class)->route('glide', ['path' => '/']),
            config('glider.sign_key')
        ));

        $this->app->bind(Server::class, fn (Application $app): Server => ServerFactory::create(
            array_merge(config('glider'), ['response' => $app->make(ResponseFactory::class)])
        ));

    }
}
