<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\Dimensions;
use Daikazu\LaravelGlider\Support\FilesystemResolver;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['glider.source' => __DIR__ . '/../fixtures']);
});

function dimensionsInstance(): Dimensions
{
    return new Dimensions(new FilesystemResolver);
}

/**
 * Create a real image file with specific intrinsic dimensions, so
 * Dimensions::transformed() can be exercised end-to-end (it is final,
 * so it cannot be subclassed/mocked in tests).
 */
function makeDimensionsFixture(string $name, int $width, int $height): string
{
    $path = __DIR__ . '/../fixtures/' . $name;
    @mkdir(dirname($path), 0755, true);

    $image = imagecreate($width, $height);
    imagecolorallocate($image, 255, 255, 255);
    imagepng($image, $path);
    imagedestroy($image);

    return $path;
}

it('returns null intrinsic dimensions for non-existent files', function () {
    expect(dimensionsInstance()->intrinsic('non-existent.jpg'))->toBeNull();
});

it('returns null intrinsic dimensions when the source root is non-local', function () {
    Storage::fake('assets');
    config(['glider.source' => ['disk' => 'assets']]);

    expect(dimensionsInstance()->intrinsic('anything.jpg'))->toBeNull();
});

it('reads intrinsic dimensions from a real image file', function () {
    $path = makeDimensionsFixture('dim-basic.png', 12, 34);

    $dims = dimensionsInstance()->intrinsic('dim-basic.png');

    expect($dims)->not->toBeNull()
        ->and($dims['width'])->toBe(12)
        ->and($dims['height'])->toBe(34);

    @unlink($path);
});

it('returns null transformed dimensions when the source image is missing', function () {
    expect(dimensionsInstance()->transformed('non-existent.jpg', ['w' => 50]))->toBeNull();
});

it('calculates transformed dimensions with width only', function () {
    $path = makeDimensionsFixture('dim-w-only.png', 100, 200);

    $transformed = dimensionsInstance()->transformed('dim-w-only.png', ['w' => 50]);

    expect($transformed['width'])->toBe(50);
    expect($transformed['height'])->toBe(100); // 200 * (50/100)

    @unlink($path);
});

it('calculates transformed dimensions with height only', function () {
    $path = makeDimensionsFixture('dim-h-only.png', 200, 400);

    $transformed = dimensionsInstance()->transformed('dim-h-only.png', ['h' => 100]);

    expect($transformed['width'])->toBe(50); // 200 * (100/400)
    expect($transformed['height'])->toBe(100);

    @unlink($path);
});

it('calculates transformed dimensions with both width and height for crop fit', function () {
    $path = makeDimensionsFixture('dim-crop.png', 400, 600);

    $transformed = dimensionsInstance()->transformed('dim-crop.png', ['w' => 300, 'h' => 200, 'fit' => 'crop']);

    expect($transformed['width'])->toBe(300);
    expect($transformed['height'])->toBe(200);

    @unlink($path);
});

it('calculates transformed dimensions with both width and height for contain fit', function () {
    $path = makeDimensionsFixture('dim-contain.png', 400, 600);

    $transformed = dimensionsInstance()->transformed('dim-contain.png', ['w' => 300, 'h' => 200, 'fit' => 'contain']);

    // Scale = min(300/400, 200/600) = min(0.75, 0.333) = 0.333
    expect($transformed['width'])->toBe(133); // 400 * 0.333 rounded
    expect($transformed['height'])->toBe(200); // 600 * 0.333 rounded

    @unlink($path);
});

it('applies device pixel ratio correctly', function () {
    $path = makeDimensionsFixture('dim-dpr.png', 200, 200);

    $transformed = dimensionsInstance()->transformed('dim-dpr.png', ['w' => 100, 'dpr' => 2]);

    expect($transformed['width'])->toBe(200); // 100 * 2
    expect($transformed['height'])->toBe(200); // 100 * 2

    @unlink($path);
});

it('handles invalid or zero dimensions gracefully', function () {
    $path = makeDimensionsFixture('dim-zero-params.png', 100, 100);

    $transformed = dimensionsInstance()->transformed('dim-zero-params.png', ['w' => 0, 'h' => 0]);

    // Should fallback to original dimensions when invalid values provided
    expect($transformed['width'])->toBe(100);
    expect($transformed['height'])->toBe(100);

    @unlink($path);
});

it('handles different fit modes correctly', function () {
    $path = makeDimensionsFixture('dim-fits.png', 400, 600);

    $testCases = [
        'crop'    => true,    // fill-like
        'fill'    => true,    // fill-like
        'stretch' => true, // fill-like
        'contain' => false, // contain-like
        'max'     => false,     // contain-like
        'unknown' => false, // contain-like (default)
    ];

    foreach ($testCases as $fit => $shouldBeFillLike) {
        $transformed = dimensionsInstance()->transformed('dim-fits.png', ['w' => 300, 'h' => 200, 'fit' => $fit]);

        if ($shouldBeFillLike) {
            expect($transformed['width'])->toBe(300, "Failed for fit: {$fit}");
            expect($transformed['height'])->toBe(200, "Failed for fit: {$fit}");
        } else {
            expect($transformed['width'])->toBeLessThanOrEqual(300, "Failed for fit: {$fit}");
            expect($transformed['height'])->toBeLessThanOrEqual(200, "Failed for fit: {$fit}");
        }
    }

    @unlink($path);
});

it('ensures minimum dimensions of 1', function () {
    $path = makeDimensionsFixture('dim-min.png', 1000, 1000);

    $transformed = dimensionsInstance()->transformed('dim-min.png', ['w' => 1, 'h' => 1]);

    expect($transformed['width'])->toBeGreaterThanOrEqual(1);
    expect($transformed['height'])->toBeGreaterThanOrEqual(1);

    @unlink($path);
});
