<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

it('clears a local path cache but preserves the gitignore', function () {
    $dir = sys_get_temp_dir() . '/glider-clear-test';
    File::deleteDirectory($dir);
    File::ensureDirectoryExists($dir . '/sub');
    File::put($dir . '/a.webp', 'x');
    File::put($dir . '/sub/b.webp', 'x');
    File::put($dir . '/.gitignore', "*\n!.gitignore\n");

    config()->set('glider.cache', $dir);

    $this->artisan('glider:clear', ['--force' => true])->assertSuccessful();

    expect(File::exists($dir . '/a.webp'))->toBeFalse()
        ->and(File::exists($dir . '/sub/b.webp'))->toBeFalse()
        ->and(File::exists($dir . '/.gitignore'))->toBeTrue();

    File::deleteDirectory($dir);
});

it('clears a disk-based cache without crashing', function () {
    Storage::fake('cache-disk');
    Storage::disk('cache-disk')->put('glider-cache/a/b.webp', 'x');
    Storage::disk('cache-disk')->put('other/keep.txt', 'x');

    config()->set('glider.cache', ['disk' => 'cache-disk', 'prefix' => 'glider-cache']);

    $this->artisan('glider:clear', ['--force' => true])->assertSuccessful();

    expect(Storage::disk('cache-disk')->exists('glider-cache/a/b.webp'))->toBeFalse()
        ->and(Storage::disk('cache-disk')->exists('other/keep.txt'))->toBeTrue();
});

it('clears only the --cache-path target, leaving the configured cache alone', function () {
    $baked = sys_get_temp_dir() . '/glider-clear-baked';
    $runtime = sys_get_temp_dir() . '/glider-clear-runtime';
    File::deleteDirectory($baked);
    File::deleteDirectory($runtime);
    File::ensureDirectoryExists($baked);
    File::ensureDirectoryExists($runtime);
    File::put($baked . '/a.webp', 'x');
    File::put($runtime . '/b.webp', 'x');

    config()->set('glider.cache', $runtime);

    $this->artisan('glider:clear', ['--force' => true, '--cache-path' => $baked])->assertSuccessful();

    expect(File::exists($baked . '/a.webp'))->toBeFalse()
        ->and(File::exists($runtime . '/b.webp'))->toBeTrue();

    File::deleteDirectory($baked);
    File::deleteDirectory($runtime);
});

it('anchors a relative cache path to the application root when clearing', function () {
    $dir = base_path('glider-clear-rel-test');
    File::deleteDirectory($dir);
    File::ensureDirectoryExists($dir);
    File::put($dir . '/a.webp', 'x');

    config()->set('glider.cache', 'glider-clear-rel-test');

    $this->artisan('glider:clear', ['--force' => true])->assertSuccessful();

    expect(File::exists($dir . '/a.webp'))->toBeFalse();

    File::deleteDirectory($dir);
});
