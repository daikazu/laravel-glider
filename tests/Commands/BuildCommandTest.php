<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Components\Img;
use Daikazu\LaravelGlider\Components\ImgResponsive;
use Daikazu\LaravelGlider\Facades\Glider;
use Illuminate\Support\Facades\File;
use Illuminate\View\ComponentAttributeBag;

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

it('prebuilds exactly the cache entry a live request would use when an explicit fm differs from the default format', function () {
    // Regression coverage: ParamResolver's redundant-fm-stripping/extension
    // logic behaves differently on the build's single decode pass vs the
    // runtime's encode -> decode -> `$params['fm'] ??= $extension` flow.
    // An explicit `fm` that differs from `glider.defaults.fm` (webp) is
    // exactly the case that previously diverged (build wrote .png, the
    // live request wrote .webp for the "same" conversion).
    // A distinct width from the shared `page.blade.php` fixture (glide-w=10,
    // no fm) is deliberate: that fixture's job happens to canonicalize to
    // the same final cache entry a `w=11` conversion never needs, so reusing
    // w=10 here would let the *other* job accidentally pre-warm this test's
    // target and mask the divergence being tested for.
    File::put(
        __DIR__ . '/../fixtures/build-views/fm-page.blade.php',
        '<x-glider-img src="test-tiny.jpg" glide-w="11" glide-fm="png" />'
    );

    $this->artisan('glider:build')->assertSuccessful();

    config()->set('glider.secure', false);

    $component = new Img('test-tiny.jpg');
    $component->attributes = new ComponentAttributeBag(['glide-w' => '11', 'glide-fm' => 'png']);
    $url = $component->src();

    $filesBefore = count(File::allFiles($this->cacheDir));
    $this->get($url)->assertOk();

    expect(count(File::allFiles($this->cacheDir)))->toBe($filesBefore);
});

it('prebuilds exactly the cache entries a live request would use for a preset on img-responsive', function () {
    // Regression coverage: imgResponsiveJobs() never mapped `preset` to
    // `p`, so a `glide-preset` attribute survived as a raw, meaningless
    // `preset` key and the preset's params were never applied at build
    // time even though the live component fully resolves them.
    config()->set('glider.presets.tiny', ['w' => 5, 'h' => 5, 'fit' => 'crop']);

    File::put(
        __DIR__ . '/../fixtures/build-views/preset-page.blade.php',
        '<x-glider-img-responsive src="test-tiny.jpg" glide-preset="tiny" srcset-widths="10" />'
    );

    $this->artisan('glider:build')->assertSuccessful();

    config()->set('glider.secure', false);

    $component = new ImgResponsive('test-tiny.jpg', '10');
    $component->attributes = new ComponentAttributeBag(['glide-preset' => 'tiny']);

    $srcUrl = $component->src();
    $srcsetUrls = collect(explode(',', (string) $component->srcset()))
        ->map(fn (string $entry): string => trim(explode(' ', trim($entry))[0]))
        ->filter()
        ->values();

    expect($srcsetUrls)->not->toBeEmpty();

    $filesBefore = count(File::allFiles($this->cacheDir));

    $this->get($srcUrl)->assertOk();

    foreach ($srcsetUrls as $srcsetUrl) {
        $this->get($srcsetUrl)->assertOk();
    }

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
