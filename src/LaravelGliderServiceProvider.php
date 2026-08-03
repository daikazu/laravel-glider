<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider;

use Daikazu\LaravelGlider\Commands\ClearGlideCacheCommand;
use Daikazu\LaravelGlider\Commands\ConvertImageTagsToGliderCommand;
use Daikazu\LaravelGlider\Components\Bg;
use Daikazu\LaravelGlider\Components\BgResponsive;
use Daikazu\LaravelGlider\Components\Img;
use Daikazu\LaravelGlider\Components\ImgResponsive;
use Daikazu\LaravelGlider\Facades\Glider;
use Daikazu\LaravelGlider\Factories\ResponseFactory;
use Daikazu\LaravelGlider\Security\PathValidator;
use Daikazu\LaravelGlider\Support\FilesystemResolver;
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
            ->hasConfigFile('glider')
            ->hasViews()
            ->hasViewComponents('glider', Img::class, ImgResponsive::class, Bg::class, BgResponsive::class)
            ->hasRoute('web')
            ->hasCommands(ClearGlideCacheCommand::class, ConvertImageTagsToGliderCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app->singleton(Glider::class, GlideService::class);

        $this->app->bind(PathValidator::class, fn (Application $app): PathValidator => new PathValidator(
            $app->make(FilesystemResolver::class)->localPath(config('glider.source'))
        ));

        $this->app->instance(SignatureInterface::class, SignatureFactory::create((string) config('glider.sign_key', '')));

        $this->app->bind(UrlBuilder::class, fn (Application $app): UrlBuilder => UrlBuilderFactory::create(
            $app->make(UrlGenerator::class)->route('glider', ['path' => '/']),
            config('glider.sign_key')
        ));

        $this->app->bind(Server::class, function (Application $app): Server {
            $resolver = $app->make(FilesystemResolver::class);
            $config = config('glider');

            return ServerFactory::create(array_merge($config, [
                'source'     => $resolver->resolve($config['source']),
                'cache'      => $resolver->resolve($config['cache']),
                'watermarks' => $resolver->resolve($config['watermarks']),
                'response'   => $app->make(ResponseFactory::class),
            ]));
        });

        $this->ensureCacheDirectoryExists();
    }

    /**
     * Ensure the cache directory exists and has a .gitignore file
     */
    protected function ensureCacheDirectoryExists(): void
    {
        if (! is_string(config('glider.cache'))) {
            return;
        }

        $cachePath = (string) config('glider.cache');

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
