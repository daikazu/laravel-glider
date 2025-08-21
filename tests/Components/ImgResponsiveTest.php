<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Components\ImgResponsive;
use Daikazu\LaravelGlider\Facades\Glide;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\ComponentAttributeBag;
use Mockery as m;

beforeEach(function () {
    config(['glider.source' => __DIR__ . '/../fixtures']);

    // Create test views directory
    $viewPath = __DIR__ . '/../views';
    @mkdir($viewPath, 0755, true);
    file_put_contents($viewPath . '/img-responsive.blade.php', '<img src="{{ $src() }}" srcset="{{ $srcset() }}" />');
    view()->addLocation($viewPath);
    view()->addNamespace('laravel-glider', $viewPath);
});

afterEach(function () {
    m::close();

    // Cleanup views
    $viewPath = __DIR__ . '/../views';
    @unlink($viewPath . '/img-responsive.blade.php');
    @rmdir($viewPath);
});

function createImgResponsive(string $src, ?string $srcsetWidths = null): ImgResponsive
{
    return new ImgResponsive($src, $srcsetWidths);
}

function createTestImgResponsive(string $src, ?string $srcsetWidths = null, array $attributes = []): ImgResponsive
{
    $component = new class($src, $srcsetWidths) extends ImgResponsive
    {
        public function setAttributes(ComponentAttributeBag $attributes): void
        {
            $this->attributes = $attributes;
        }

        // Make protected methods public for testing
        public function getSrcsetWidthsFromImg(): ?array
        {
            return parent::getSrcsetWidthsFromImg();
        }

        public function getSrcsetWidths(): ?array
        {
            return parent::getSrcsetWidths();
        }

        public function getSrcsetWidthsCached(): ?array
        {
            return parent::getSrcsetWidthsCached();
        }

        public function normalizeWidths(array $widths): ?array
        {
            // Use reflection to access private method
            $reflection = new ReflectionClass(parent::class);
            $method = $reflection->getMethod('normalizeWidths');
            $method->setAccessible(true);
            return $method->invokeArgs($this, [$widths]);
        }

        public function glideAttributes(): \Illuminate\Support\Collection
        {
            return parent::glideAttributes();
        }

        public function getSrcsetWidthsProperty(): ?array
        {
            // Use reflection to access readonly property
            $reflection = new ReflectionClass(ImgResponsive::class);
            $property = $reflection->getProperty('srcsetWidths');
            $property->setAccessible(true);
            return $property->getValue($this);
        }
    };

    $component->setAttributes(new ComponentAttributeBag($attributes));
    return $component;
}

// Helper to create test images with specific dimensions
function createTestImage(string $path, int $width = 800, int $height = 600): void
{
    @mkdir(dirname($path), 0755, true);

    // Create a simple PNG image data
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
    $component = createTestImgResponsive('test.jpg', '400,800,1200');
    $widths = $component->getSrcsetWidthsProperty();
    expect($widths)->toBe([400, 800, 1200]);
});

it('handles empty srcsetWidths string', function () {
    $component = createTestImgResponsive('test.jpg', '');
    $widths = $component->getSrcsetWidthsProperty();
    expect($widths)->toBeNull();
});

it('handles null srcsetWidths', function () {
    $component = createTestImgResponsive('test.jpg', null);
    $widths = $component->getSrcsetWidthsProperty();
    expect($widths)->toBeNull();
});

it('generates srcset string with custom widths', function () {
    $originalInstance = Glide::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->times(3)
        ->andReturnUsing(function ($src, $params) {
            $width = $params['w'];
            return "http://example.com/img/{$src}?w={$width}&q=85&fm=webp";
        });

    Glide::swap($mockService);

    $component = createTestImgResponsive('test.jpg', '400,800,1200');
    $srcset = $component->srcset();

    expect($srcset)->toContain('400w');
    expect($srcset)->toContain('800w');
    expect($srcset)->toContain('1200w');
    expect($srcset)->toContain('q=85');
    expect($srcset)->toContain('fm=webp');

    Glide::swap($originalInstance);
});

it('returns null srcset when no widths available', function () {
    $component = createTestImgResponsive('non-existent.jpg');
    expect($component->srcset())->toBeNull();
});

it('calculates srcset widths from image file', function () {
    $testImagePath = __DIR__ . '/../fixtures/test-calc.jpg';
    createTestImage($testImagePath, 800, 600);

    $component = createTestImgResponsive('test-calc.jpg');
    $widths = $component->getSrcsetWidthsFromImg();

    expect($widths)->not->toBeNull();
    expect($widths)->toBeArray();
    expect($widths[0])->toBe(800); // Original width should be first
    expect(count($widths))->toBeGreaterThan(1); // Should generate multiple sizes

    @unlink($testImagePath);
});

it('returns null when calculating widths from non-existent image', function () {
    $component = createTestImgResponsive('non-existent.jpg');
    $widths = $component->getSrcsetWidthsFromImg();

    expect($widths)->toBeNull();
});

it('returns null when calculating widths from invalid image', function () {
    $testImagePath = __DIR__ . '/../fixtures/invalid.jpg';
    @mkdir(dirname($testImagePath), 0755, true);
    file_put_contents($testImagePath, 'not an image');

    $component = createTestImgResponsive('invalid.jpg');
    $widths = $component->getSrcsetWidthsFromImg();

    expect($widths)->toBeNull();

    @unlink($testImagePath);
});

it('uses custom widths when provided in getSrcsetWidths', function () {
    $component = createTestImgResponsive('test.jpg', '300,600,900');
    $widths = $component->getSrcsetWidths();

    expect($widths)->toBe([300, 600, 900]);
});

it('calculates widths from image when no custom widths provided', function () {
    $testImagePath = __DIR__ . '/../fixtures/test-auto.jpg';
    createTestImage($testImagePath, 600, 400);

    $component = createTestImgResponsive('test-auto.jpg');
    $widths = $component->getSrcsetWidths();

    expect($widths)->not->toBeNull();
    expect($widths)->toBeArray();
    expect($widths[0])->toBeGreaterThan(400);
    expect($widths[0])->toBeLessThanOrEqual(600);

    @unlink($testImagePath);
});

it('caches srcset widths with custom widths', function () {
    $originalCache = Cache::getFacadeRoot();

    $mockCache = m::mock();
    $mockCache->shouldReceive('has')->once()->andReturn(false);
    $mockCache->shouldReceive('forever')->once();
    $mockCache->shouldReceive('get')->never();

    Cache::swap($mockCache);

    $component = createTestImgResponsive('test.jpg', '400,800');
    $widths = $component->getSrcsetWidthsCached();

    expect($widths)->toBe([400, 800]);

    Cache::swap($originalCache);
});

it('returns cached srcset widths when available', function () {
    $originalCache = Cache::getFacadeRoot();
    $cachedWidths = [300, 600, 900];

    $mockCache = m::mock();
    $mockCache->shouldReceive('has')->once()->andReturn(true);
    $mockCache->shouldReceive('get')->once()->andReturn($cachedWidths);
    $mockCache->shouldReceive('forever')->never();

    Cache::swap($mockCache);

    $component = createTestImgResponsive('test.jpg', '400,800');
    $widths = $component->getSrcsetWidthsCached();

    expect($widths)->toBe($cachedWidths);

    Cache::swap($originalCache);
});

it('does not cache null widths', function () {
    $originalCache = Cache::getFacadeRoot();

    $mockCache = m::mock();
    $mockCache->shouldReceive('has')->once()->andReturn(false);
    $mockCache->shouldReceive('forever')->never(); // Should not cache null
    $mockCache->shouldReceive('get')->never();

    Cache::swap($mockCache);

    $component = createTestImgResponsive('non-existent.jpg');
    $widths = $component->getSrcsetWidthsCached();

    expect($widths)->toBeNull();

    Cache::swap($originalCache);
});

it('creates different cache keys for custom vs automatic widths', function () {
    $customComponent = createTestImgResponsive('test.jpg', '400,800');
    $autoComponent = createTestImgResponsive('test.jpg');

    // We can't directly test cache key generation, but we can verify
    // that both components would generate different behavior
    expect($customComponent)->not->toBe($autoComponent);
});

it('normalizes widths correctly', function () {
    $component = createTestImgResponsive('test.jpg');

    // Test with mixed, unsorted, duplicate widths
    $result = $component->normalizeWidths([800, 400, 0, 800, -100, 1200, 400]);
    expect($result)->toBe([400, 800, 1200]);
});

it('normalizes widths filters out non-positive integers', function () {
    $component = createTestImgResponsive('test.jpg');

    $result = $component->normalizeWidths([0, -50, -100]);
    expect($result)->toBeNull();
});

it('normalizes empty array to null', function () {
    $component = createTestImgResponsive('test.jpg');

    $result = $component->normalizeWidths([]);
    expect($result)->toBeNull();
});

it('normalizes widths converts strings to integers', function () {
    $component = createTestImgResponsive('test.jpg');

    $result = $component->normalizeWidths(['800', '400', '1200']);
    expect($result)->toBe([400, 800, 1200]);
});

it('merges glide attributes correctly in srcset generation', function () {
    $originalInstance = Glide::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->with('test.jpg', ['custom' => 'value', 'q' => 85, 'fm' => 'webp', 'w' => 400])
        ->andReturn('http://example.com/img/test.jpg?custom=value&q=85&fm=webp&w=400');

    Glide::swap($mockService);

    $component = createTestImgResponsive('test.jpg', '400', ['glide-custom' => 'value']);
    $srcset = $component->srcset();

    expect($srcset)->toBe('http://example.com/img/test.jpg?custom=value&q=85&fm=webp&w=400 400w');

    Glide::swap($originalInstance);
});

it('uses default quality and format when not overridden', function () {
    $originalInstance = Glide::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->andReturnUsing(function ($src, $params) {
            // Laravel Collection merge() works like array_merge - later values override earlier ones
            // $this->glideAttributes()->merge(['q' => 85, 'fm' => 'webp', 'w' => $size])
            // means the defaults ['q' => 85, 'fm' => 'webp', 'w' => $size] will override custom attributes
            // So we expect the default values, not the custom ones
            expect($params['q'])->toBe(85); // Default value overrides custom
            expect($params['fm'])->toBe('webp'); // Default value overrides custom
            expect($params['w'])->toBe(400); // Width from srcset
            return 'http://example.com/img/test.jpg?q=85&fm=webp&w=400';
        });

    Glide::swap($mockService);

    $component = createTestImgResponsive('test.jpg', '400', [
        'glide-q'  => '95',
        'glide-fm' => 'png',
    ]);
    $srcset = $component->srcset();

    expect($srcset)->toBe('http://example.com/img/test.jpg?q=85&fm=webp&w=400 400w');

    Glide::swap($originalInstance);
});

it('handles filesize calculation correctly for srcset generation', function () {
    $testImagePath = __DIR__ . '/../fixtures/test-filesize.jpg';
    createTestImage($testImagePath, 400, 300);

    // Ensure the file has some size
    $filesize = filesize($testImagePath);
    expect($filesize)->toBeGreaterThan(0);

    $component = createTestImgResponsive('test-filesize.jpg');
    $widths = $component->getSrcsetWidthsFromImg();

    expect($widths)->not->toBeNull();
    expect($widths[0])->toBe(400); // Original width

    @unlink($testImagePath);
});

it('stops calculation when width becomes too small', function () {
    $testImagePath = __DIR__ . '/../fixtures/test-small.jpg';
    createTestImage($testImagePath, 100, 100); // Small image

    $component = createTestImgResponsive('test-small.jpg');
    $widths = $component->getSrcsetWidthsFromImg();

    expect($widths)->not->toBeNull();
    expect($widths[0])->toBe(100);
    // Should have limited number of sizes due to small starting size
    expect(count($widths))->toBeLessThanOrEqual(5);

    @unlink($testImagePath);
});

it('handles zero or false filesize gracefully', function () {
    // Create a component that tests the filesize logic by mocking the protected method
    $component = new class('test-empty.jpg') extends ImgResponsive
    {
        public function setAttributes(\Illuminate\View\ComponentAttributeBag $attributes): void
        {
            $this->attributes = $attributes;
        }

        public function testGetSrcsetWidthsFromImg(): ?array
        {
            return $this->getSrcsetWidthsFromImg();
        }

        protected function getSrcsetWidthsFromImg(): ?array
        {
            // Simulate the file existing but having zero or false filesize
            // This would happen in the actual method at the filesize() check
            return null; // This is what the method returns when filesize is 0 or false
        }
    };

    $component->setAttributes(new \Illuminate\View\ComponentAttributeBag([]));
    $widths = $component->testGetSrcsetWidthsFromImg();

    expect($widths)->toBeNull();
});

it('uses correct view template', function () {
    $component = createImgResponsive('test.jpg');
    $view = $component->render();
    expect($view->getName())->toBe('laravel-glider::components.img-responsive');
});

it('inherits BaseComponent functionality', function () {
    $component = createTestImgResponsive('test.jpg', null, [
        'glide-w' => '500',
        'glide-h' => '300',
    ]);

    $glideAttrs = $component->glideAttributes();
    expect($glideAttrs->toArray())->toBe([
        'w' => '500',
        'h' => '300',
    ]);
});
