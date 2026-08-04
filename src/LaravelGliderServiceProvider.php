<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider;

use Daikazu\LaravelGlider\Commands\BuildCommand;
use Daikazu\LaravelGlider\Commands\ClearGlideCacheCommand;
use Daikazu\LaravelGlider\Commands\ConvertImageTagsToGliderCommand;
use Daikazu\LaravelGlider\Components\Bg;
use Daikazu\LaravelGlider\Components\BgResponsive;
use Daikazu\LaravelGlider\Components\Img;
use Daikazu\LaravelGlider\Components\ImgResponsive;
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
            ->hasCommands(ClearGlideCacheCommand::class, ConvertImageTagsToGliderCommand::class, BuildCommand::class);
    }

    public function packageBooted(): void
    {
        $this->app->singleton(Glider::class);

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
     * Ensure a local cache directory exists. Caches outside public_path get a
     * .gitignore so runtime artifacts stay out of version control; a cache
     * under public_path is deliberately web-served (and often committed or
     * shipped in the release artifact), so it is left visible to git.
     */
    protected function ensureCacheDirectoryExists(): void
    {
        $cache = config('glider.cache');

        if (! is_string($cache) || $cache === '') {
            return;
        }

        $cachePath = app(FilesystemResolver::class)->localPath($cache);

        $filesystem = app('files');

        if (! $filesystem->isDirectory($cachePath)) {
            $filesystem->makeDirectory($cachePath, 0755, true);
        }

        if (str_starts_with((string) $cachePath, public_path())) {
            return;
        }

        $gitignorePath = $cachePath . '/.gitignore';
        if (! $filesystem->exists($gitignorePath)) {
            $filesystem->put($gitignorePath, "*\n!.gitignore\n");
        }
    }
}
