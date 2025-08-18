<?php

namespace Daikazu\LaravelGlider;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Daikazu\LaravelGlider\Commands\LaravelGliderCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel_glider_table')
            ->hasCommand(LaravelGliderCommand::class);
    }
}
