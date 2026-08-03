<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\SrcsetCalculator;
use Illuminate\Support\Facades\Cache;
use Mockery as m;

beforeEach(function () {
    config(['glider.source' => __DIR__ . '/../fixtures']);
});

afterEach(function () {
    m::close();
});

function makeSrcsetTestImage(string $path, int $width = 800, int $height = 600): void
{
    @mkdir(dirname($path), 0755, true);

    $image = imagecreate($width, $height);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    imagefill($image, 0, 0, $white);
    imagestring($image, 5, 10, 10, 'Test Image', $black);

    imagepng($image, $path);
    imagedestroy($image);
}

it('returns custom widths normalized ascending unique', function () {
    expect(app(SrcsetCalculator::class)->widths('missing.jpg', [800, 400, 800]))->toBe([400, 800]);
});

it('returns null for a non-existent image with no custom widths', function () {
    expect(app(SrcsetCalculator::class)->widths('missing.jpg', null))->toBeNull();
});

it('normalizes custom widths: filters non-positive, dedupes, sorts', function () {
    $calculator = app(SrcsetCalculator::class);

    expect($calculator->widths('missing.jpg', [800, 400, 0, 800, -100, 1200, 400]))->toBe([400, 800, 1200]);
});

it('falls back to calculated widths when custom widths normalize to nothing', function () {
    $path = __DIR__ . '/../fixtures/srcset-fallback.jpg';
    makeSrcsetTestImage($path, 800, 600);

    $widths = app(SrcsetCalculator::class)->widths('srcset-fallback.jpg', [0, -50, -100]);

    expect($widths)->not->toBeNull()
        ->and($widths[0])->toBeGreaterThan(0);

    @unlink($path);
});

it('calculates widths from the image when no custom widths are provided', function () {
    $path = __DIR__ . '/../fixtures/srcset-auto.jpg';
    makeSrcsetTestImage($path, 800, 600);

    $widths = app(SrcsetCalculator::class)->widths('srcset-auto.jpg', null);

    expect($widths)->not->toBeNull()
        ->and($widths)->toBeArray()
        ->and(count($widths))->toBeGreaterThan(1);

    @unlink($path);
});

it('stops calculation when width becomes too small', function () {
    $path = __DIR__ . '/../fixtures/srcset-small.jpg';
    makeSrcsetTestImage($path, 100, 100);

    $widths = app(SrcsetCalculator::class)->widths('srcset-small.jpg', null);

    expect($widths)->not->toBeNull();
    expect(count($widths))->toBeLessThanOrEqual(5);

    @unlink($path);
});

it('returns null when the image file is invalid', function () {
    $path = __DIR__ . '/../fixtures/srcset-invalid.jpg';
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, 'not an image');

    expect(app(SrcsetCalculator::class)->widths('srcset-invalid.jpg', null))->toBeNull();

    @unlink($path);
});

it('caches computed custom widths using a glider: prefixed key', function () {
    $originalCache = Cache::getFacadeRoot();

    $mockCache = m::mock();
    $mockCache->shouldReceive('has')->once()->andReturn(false);
    $mockCache->shouldReceive('forever')
        ->once()
        ->withArgs(fn (string $key, array $value): bool => str_starts_with($key, 'glider:') && $value === [400, 800])
        ->andReturn(true);
    $mockCache->shouldReceive('get')->never();

    Cache::swap($mockCache);

    $widths = app(SrcsetCalculator::class)->widths('test.jpg', [400, 800]);

    expect($widths)->toBe([400, 800]);

    Cache::swap($originalCache);
});

it('returns cached widths when available without recomputing', function () {
    $originalCache = Cache::getFacadeRoot();
    $cachedWidths = [300, 600, 900];

    $mockCache = m::mock();
    $mockCache->shouldReceive('has')->once()->andReturn(true);
    $mockCache->shouldReceive('get')->once()->andReturn($cachedWidths);
    $mockCache->shouldReceive('forever')->never();

    Cache::swap($mockCache);

    $widths = app(SrcsetCalculator::class)->widths('test.jpg', [400, 800]);

    expect($widths)->toBe($cachedWidths);

    Cache::swap($originalCache);
});

it('does not cache a null result', function () {
    $originalCache = Cache::getFacadeRoot();

    $mockCache = m::mock();
    $mockCache->shouldReceive('has')->once()->andReturn(false);
    $mockCache->shouldReceive('forever')->never();
    $mockCache->shouldReceive('get')->never();

    Cache::swap($mockCache);

    $widths = app(SrcsetCalculator::class)->widths('non-existent.jpg', null);

    expect($widths)->toBeNull();

    Cache::swap($originalCache);
});
