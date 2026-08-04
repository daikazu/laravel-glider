<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider;

use Composer\InstalledVersions;
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
use Illuminate\Foundation\Console\AboutCommand;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureInterface;
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
        $this->registerAboutCommand();
    }

    protected function registerAboutCommand(): void
    {
        AboutCommand::add('Glider', fn (): array => [
            'Version'  => InstalledVersions::getPrettyVersion('daikazu/laravel-glider') ?? 'unknown',
            'Driver'   => (string) config('glider.driver'),
            'Base URL' => '/' . trim((string) config('glider.base_url'), '/'),
            'Source'   => app(FilesystemResolver::class)->describe(config('glider.source')),
            'Cache'    => app(FilesystemResolver::class)->describe(config('glider.cache')),

            'Signed URLs' => config('glider.secure', true)
                ? '<fg=green;options=bold>ENABLED</>'
                : '<fg=red;options=bold>DISABLED</>',

            'On-the-fly' => config('glider.on_the_fly', true)
                ? '<fg=green;options=bold>ENABLED</>'
                : '<fg=yellow;options=bold>DISABLED</>',

            'Presets Only' => config('glider.restrict_to_presets', false)
                ? '<fg=green;options=bold>ENABLED</>'
                : '<fg=yellow;options=bold>DISABLED</>',
        ]);
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
