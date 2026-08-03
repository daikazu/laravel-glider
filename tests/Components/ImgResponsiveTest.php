<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Components\ImgResponsive;
use Daikazu\LaravelGlider\Facades\Glider;
use Illuminate\View\ComponentAttributeBag;
use Mockery as m;

beforeEach(function () {
    config(['glider.source' => __DIR__ . '/../fixtures']);
});

afterEach(function () {
    m::close();
});

function createImgResponsive(string $src, ?string $srcsetWidths = null, array $attributes = []): ImgResponsive
{
    $component = new ImgResponsive($src, $srcsetWidths);
    $component->attributes = new ComponentAttributeBag($attributes);

    return $component;
}

function getSrcsetWidthsProperty(ImgResponsive $component): ?array
{
    $reflection = new ReflectionClass(ImgResponsive::class);
    $property = $reflection->getProperty('srcsetWidths');
    $property->setAccessible(true);

    return $property->getValue($component);
}

// Helper to create test images with specific dimensions
function createTestImage(string $path, int $width = 800, int $height = 600): void
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

it('can be instantiated with source path only', function () {
    $component = createImgResponsive('test.jpg');
    expect($component->src)->toBe('test.jpg');
});

it('can be instantiated with source path and srcset widths', function () {
    $component = createImgResponsive('test.jpg', '400,800,1200');
    expect($component->src)->toBe('test.jpg');
});

it('parses srcsetWidths from string correctly', function () {
    $component = createImgResponsive('test.jpg', '400,800,1200');
    expect(getSrcsetWidthsProperty($component))->toBe([400, 800, 1200]);
});

it('handles empty srcsetWidths string', function () {
    $component = createImgResponsive('test.jpg', '');
    expect(getSrcsetWidthsProperty($component))->toBeNull();
});

it('handles null srcsetWidths', function () {
    $component = createImgResponsive('test.jpg', null);
    expect(getSrcsetWidthsProperty($component))->toBeNull();
});

it('filters non-positive widths out of the srcsetWidths string', function () {
    $component = createImgResponsive('test.jpg', '0,-5,400');
    expect(getSrcsetWidthsProperty($component))->toBe([400]);
});

it('generates srcset string with custom widths', function () {
    $originalInstance = Glider::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->times(3)
        ->andReturnUsing(function ($src, $params) {
            $width = $params['w'];

            return "http://example.com/img/{$src}?w={$width}&q=85&fm=webp";
        });

    Glider::swap($mockService);

    $component = createImgResponsive('test.jpg', '400,800,1200');
    $srcset = $component->srcset();

    expect($srcset)->toContain('400w');
    expect($srcset)->toContain('800w');
    expect($srcset)->toContain('1200w');
    expect($srcset)->toContain('q=85');
    expect($srcset)->toContain('fm=webp');

    Glider::swap($originalInstance);
});

it('returns null srcset when no widths available', function () {
    $component = createImgResponsive('non-existent.jpg');
    expect($component->srcset())->toBeNull();
});

it('calculates srcset widths from the image file when none are given', function () {
    $testImagePath = __DIR__ . '/../fixtures/test-calc.jpg';
    createTestImage($testImagePath, 800, 600);

    $originalInstance = Glider::getFacadeRoot();
    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')->andReturnUsing(fn ($src, $params) => "http://example.com/{$src}?w={$params['w']}");
    Glider::swap($mockService);

    $component = createImgResponsive('test-calc.jpg');
    $srcset = $component->srcset();

    expect($srcset)->not->toBeNull();
    expect($srcset)->toContain('800w'); // Original width should be included

    Glider::swap($originalInstance);
    @unlink($testImagePath);
});

it('merges glide attributes correctly in srcset generation', function () {
    $originalInstance = Glider::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->with('test.jpg', ['custom' => 'value', 'q' => 85, 'fm' => 'webp', 'w' => 400])
        ->andReturn('http://example.com/img/test.jpg?custom=value&q=85&fm=webp&w=400');

    Glider::swap($mockService);

    $component = createImgResponsive('test.jpg', '400', ['glide-custom' => 'value']);
    $srcset = $component->srcset();

    expect($srcset)->toBe('http://example.com/img/test.jpg?custom=value&q=85&fm=webp&w=400 400w');

    Glider::swap($originalInstance);
});

it('uses default quality and format when not overridden', function () {
    $originalInstance = Glider::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->andReturnUsing(function ($src, $params) {
            // array_merge($glideAttributes, ['q' => 85, 'fm' => 'webp', 'w' => $size])
            // means the defaults on the right override the glide-* attributes.
            expect($params['q'])->toBe(85);
            expect($params['fm'])->toBe('webp');
            expect($params['w'])->toBe(400);

            return 'http://example.com/img/test.jpg?q=85&fm=webp&w=400';
        });

    Glider::swap($mockService);

    $component = createImgResponsive('test.jpg', '400', [
        'glide-q'  => '95',
        'glide-fm' => 'png',
    ]);
    $srcset = $component->srcset();

    expect($srcset)->toBe('http://example.com/img/test.jpg?q=85&fm=webp&w=400 400w');

    Glider::swap($originalInstance);
});

it('uses correct view template', function () {
    $component = createImgResponsive('test.jpg');
    $view = $component->render();
    expect($view->getName())->toBe('glider::components.img-responsive');
});

it('generates URLs using the Glide facade in src()', function () {
    $originalInstance = Glider::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->with('test-image.jpg', [])
        ->andReturn('http://example.com/img/abc123/def456.jpg');

    Glider::swap($mockService);

    $component = createImgResponsive('test-image.jpg');
    expect($component->src())->toBe('http://example.com/img/abc123/def456.jpg');

    Glider::swap($originalInstance);
});

it('returns null width and height for non-existent files', function () {
    $component = createImgResponsive('non-existent.jpg');
    expect($component->width())->toBeNull();
    expect($component->height())->toBeNull();
});

it('resolves width() and height() from real intrinsic dimensions', function () {
    $testImagePath = __DIR__ . '/../fixtures/img-responsive-test-dims.png';
    $image = imagecreate(300, 300);
    imagecolorallocate($image, 255, 255, 255);
    imagepng($image, $testImagePath);
    imagedestroy($image);

    $component = createImgResponsive('img-responsive-test-dims.png', null, ['glide-w' => '200']);

    expect($component->width())->toBe(200);
    expect($component->height())->toBe(200);

    @unlink($testImagePath);
});

it('parses the focal-point attribute into a CSS object-position value', function () {
    $component = createImgResponsive('test.jpg', null, ['focal-point' => 'center']);
    expect($component->objectPosition())->toBe('50% 50%');
});

it('returns null object-position when no focal-point attribute is present', function () {
    $component = createImgResponsive('test.jpg');
    expect($component->objectPosition())->toBeNull();
});
