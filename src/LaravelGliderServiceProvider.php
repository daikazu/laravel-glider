<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider;

use Daikazu\LaravelGlider\Commands\ClearGlideCacheCommand;
use Daikazu\LaravelGlider\Commands\ConvertImageTagsToGliderCommand;
use Daikazu\LaravelGlider\Components\Bg;
use Daikazu\LaravelGlider\Components\BgResponsive;
use Daikazu\LaravelGlider\Components\Img;
use Daikazu\LaravelGlider\Components\ImgResponsive;
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
            ->name('glider')
            ->hasConfigFile('laravel-glider')
            ->hasViews()
            ->hasViewComponents('glide', Img::class, ImgResponsive::class, Bg::class, BgResponsive::class)
            ->hasRoute('web')
            ->hasCommands(ClearGlideCacheCommand::class, ConvertImageTagsToGliderCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app->singleton(Glide::class, GlideService::class);

        $this->app->instance(SignatureInterface::class, SignatureFactory::create((string) config('laravel-glider.sign_key', '')));

        $this->app->bind(UrlBuilder::class, fn (Application $app): UrlBuilder => UrlBuilderFactory::create(
            $app->make(UrlGenerator::class)->route('glide', ['path' => '/']),
            config('laravel-glider.sign_key')
        ));

        $this->app->bind(Server::class, fn (Application $app): Server => ServerFactory::create(
            array_merge(config('laravel-glider'), ['response' => $app->make(ResponseFactory::class)])
        ));

        $this->ensureCacheDirectoryExists();
    }

    /**
     * Ensure the cache directory exists and has a .gitignore file
     */
    protected function ensureCacheDirectoryExists(): void
    {
        $cachePath = (string) config('laravel-glider.cache');

        if ($cachePath === '') {
            return;
        }

        $filesystem = app('files');

        // Create the cache directory if it doesn't exist
        if (! $filesystem->isDirectory($cachePath)) {
            $filesystem->makeDirectory($cachePath, 0755, true);
        }

        // Add .gitignore to prevent committing cached images
        $gitignorePath = $cachePath . '/.gitignore';
        if (! $filesystem->exists($gitignorePath)) {
            $filesystem->put($gitignorePath, "*\n!.gitignore\n");
        }
    }
}
