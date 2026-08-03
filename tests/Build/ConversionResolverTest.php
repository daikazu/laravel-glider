<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Build\BladeUsage;
use Daikazu\LaravelGlider\Build\ConversionResolver;

beforeEach(function () {
    config()->set('glider.source', __DIR__ . '/../fixtures');
});

it('resolves an img usage to one job with preset mapped', function () {
    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('img', 'hero.jpg', ['glide-w' => '1200', 'glide-preset' => 'thumbnail'], 'a.blade.php')
    );
    expect($jobs)->toBe([['path' => 'hero.jpg', 'params' => ['w' => '1200', 'p' => 'thumbnail']]]);
});

it('resolves a bg usage to one job with glide- prefix stripped', function () {
    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('bg', 'banner.jpg', ['glide-fit' => 'crop'], 'a.blade.php')
    );
    expect($jobs)->toBe([['path' => 'banner.jpg', 'params' => ['fit' => 'crop']]]);
});

it('resolves a url usage to one job with attributes as-is', function () {
    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('url', 'inline.jpg', ['w' => '400', 'fm' => 'webp'], 'a.blade.php')
    );
    expect($jobs)->toBe([['path' => 'inline.jpg', 'params' => ['w' => '400', 'fm' => 'webp']]]);
});

it('resolves img-responsive to one job per srcset width plus the base image', function () {
    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('img-responsive', 'test-tiny.jpg', ['srcset-widths' => '400,800'], 'a.blade.php')
    );
    $widths = collect($jobs)->pluck('params.w')->filter()->values();
    expect($widths->all())->toBe(['400', '800'])->and($jobs)->toHaveCount(3);
});

it('resolves bg-responsive presets to one job per breakpoint', function () {
    config()->set('glider.background_presets.hero', [
        'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop'],
        'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop'],
    ]);
    expect(app(ConversionResolver::class)->jobs(
        new BladeUsage('bg-responsive', 'banner.jpg', ['preset' => 'hero'], 'a.blade.php')
    ))->toHaveCount(2);
});
