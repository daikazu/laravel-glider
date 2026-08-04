<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\LaravelGliderServiceProvider;
use Illuminate\Support\Facades\File;

function bootCacheDirectorySetup(): void
{
    $provider = new LaravelGliderServiceProvider(app());
    (new ReflectionMethod($provider, 'ensureCacheDirectoryExists'))->invoke($provider);
}

it('anchors a relative cache path to the application root when creating the directory', function () {
    $relative = 'glider-cache-dir-test';
    $absolute = base_path($relative);
    File::deleteDirectory($absolute);
    File::deleteDirectory(sys_get_temp_dir() . '/' . $relative);

    config()->set('glider.cache', $relative);

    $cwd = getcwd();
    chdir(sys_get_temp_dir());

    try {
        bootCacheDirectorySetup();
        expect(File::isDirectory($absolute))->toBeTrue()
            ->and(File::isDirectory(sys_get_temp_dir() . '/' . $relative))->toBeFalse();
    } finally {
        chdir((string) $cwd);
        File::deleteDirectory($absolute);
    }
});

it('does not write a gitignore into a cache directory under public_path', function () {
    $dir = public_path('glider-public-cache-test');
    File::deleteDirectory($dir);

    config()->set('glider.cache', $dir);
    bootCacheDirectorySetup();

    try {
        expect(File::isDirectory($dir))->toBeTrue()
            ->and(File::exists($dir . '/.gitignore'))->toBeFalse();
    } finally {
        File::deleteDirectory($dir);
    }
});

it('writes a gitignore into a cache directory outside public_path', function () {
    $dir = storage_path('glider-storage-cache-test');
    File::deleteDirectory($dir);

    config()->set('glider.cache', $dir);
    bootCacheDirectorySetup();

    try {
        expect(File::get($dir . '/.gitignore'))->toBe("*\n!.gitignore\n");
    } finally {
        File::deleteDirectory($dir);
    }
});

it('does nothing for disk-based cache config', function () {
    config()->set('glider.cache', ['disk' => 's3']);
    bootCacheDirectorySetup();
})->throwsNoExceptions();
