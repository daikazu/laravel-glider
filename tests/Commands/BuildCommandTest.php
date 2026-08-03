<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Facades\Glider;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    config()->set('glider.build.paths', [__DIR__ . '/../fixtures/build-views']);
    config()->set('glider.source', __DIR__ . '/../fixtures');
    config()->set('glider.cache', $this->cacheDir = sys_get_temp_dir() . '/glider-build-test');
    File::deleteDirectory($this->cacheDir);
    File::ensureDirectoryExists(__DIR__ . '/../fixtures/build-views');
    File::put(
        __DIR__ . '/../fixtures/build-views/page.blade.php',
        '<x-glider-img src="test-tiny.jpg" glide-w="10" />'
    );
});

afterEach(function () {
    File::deleteDirectory(__DIR__ . '/../fixtures/build-views');
});

it('prebuilds exactly the cache entry a live request would use', function () {
    $this->artisan('glider:build')->assertSuccessful();

    config()->set('glider.secure', false);
    $filesBefore = count(File::allFiles($this->cacheDir));
    $this->get(Glider::url('test-tiny.jpg', ['w' => '10']))->assertOk();

    // THE invariant: serving the same conversion creates no new cache file.
    expect(count(File::allFiles($this->cacheDir)))->toBe($filesBefore);
});

it('lists jobs without generating on --dry-run', function () {
    $this->artisan('glider:build', ['--dry-run' => true])->assertSuccessful();
    expect(File::exists($this->cacheDir) ? File::allFiles($this->cacheDir) : [])->toBeEmpty();
});

it('reports dynamic usages and exits non-zero on failures', function () {
    File::put(
        __DIR__ . '/../fixtures/build-views/broken.blade.php',
        '<x-glider-img src="does-not-exist.jpg" /><x-glider-img :src="$x" />'
    );
    $this->artisan('glider:build')
        ->expectsOutputToContain('skipped (dynamic src)')
        ->assertFailed();
});
