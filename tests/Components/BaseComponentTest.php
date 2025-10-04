<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Components\BaseComponent;
use Daikazu\LaravelGlider\Facades\Glide;
use Illuminate\View\ComponentAttributeBag;
use Mockery as m;

beforeEach(function () {
    config(['laravel-glider.source' => __DIR__ . '/../fixtures']);

    // Create a basic test view
    $viewPath = __DIR__ . '/../views';
    @mkdir($viewPath, 0755, true);
    file_put_contents($viewPath . '/test-view.blade.php', '<img src="test" />');
    view()->addLocation($viewPath);
});

afterEach(function () {
    m::close();

    // Cleanup view
    $viewPath = __DIR__ . '/../views';
    @unlink($viewPath . '/test-view.blade.php');
    @rmdir($viewPath);
});

function createTestComponent(string $src, array $attributes = []): BaseComponent
{
    $component = new class($src) extends BaseComponent
    {
        protected string $view = 'test-view';

        public function setAttributes(ComponentAttributeBag $attributes): void
        {
            $this->attributes = $attributes;
        }

        // Make protected methods public for testing
        public function glideAttributes(): \Illuminate\Support\Collection
        {
            return parent::glideAttributes();
        }

        public function dimensions(): ?array
        {
            return parent::dimensions();
        }

        public function transformedDimensions(): ?array
        {
            return parent::transformedDimensions();
        }
    };

    $component->setAttributes(new ComponentAttributeBag($attributes));

    return $component;
}

// Helper function to create test component with mocked dimensions
function createMockedDimensionsComponent(string $src, array $attributes = [], ?array $mockedDimensions = null): BaseComponent
{
    $component = new class($src, $mockedDimensions) extends BaseComponent
    {
        protected string $view = 'test-view';

        private ?array $mockedDimensions;

        public function __construct(string $src, ?array $mockedDimensions = null)
        {
            parent::__construct($src);
            $this->mockedDimensions = $mockedDimensions;
        }

        public function setAttributes(ComponentAttributeBag $attributes): void
        {
            $this->attributes = $attributes;
        }

        public function glideAttributes(): \Illuminate\Support\Collection
        {
            return parent::glideAttributes();
        }

        public function transformedDimensions(): ?array
        {
            return parent::transformedDimensions();
        }

        protected function dimensions(): ?array
        {
            return $this->mockedDimensions ?? parent::dimensions();
        }
    };

    $component->setAttributes(new ComponentAttributeBag($attributes));

    return $component;
}

it('can be instantiated with a source path', function () {
    $component = createTestComponent('test-image.jpg');
    expect($component->src)->toBe('test-image.jpg');
});

it('returns the configured view when render is called', function () {
    $component = createTestComponent('test.jpg');
    $view = $component->render();
    expect($view->getName())->toBe('test-view');
});

it('generates URLs using Glide facade in src method', function () {
    // Mock the Glide facade by replacing it entirely
    $originalInstance = Glide::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->with('test-image.jpg', [])
        ->andReturn('http://example.com/img/abc123/def456.jpg');

    Glide::swap($mockService);

    $component = createTestComponent('test-image.jpg');
    $result = $component->src();

    expect($result)->toBe('http://example.com/img/abc123/def456.jpg');

    // Restore original instance
    Glide::swap($originalInstance);
});

it('passes glide attributes to Glide facade', function () {
    $originalInstance = Glide::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->with('test.jpg', ['w' => '300', 'h' => '200', 'q' => '85'])
        ->andReturn('http://example.com/img/processed.jpg');

    Glide::swap($mockService);

    $component = createTestComponent('test.jpg', [
        'glide-w' => '300',
        'glide-h' => '200',
        'glide-q' => '85',
        'class'   => 'some-class', // non-glide attribute should be ignored
    ]);

    $component->src();

    Glide::swap($originalInstance);
});

it('extracts glide attributes correctly', function () {
    $component = createTestComponent('test.jpg', [
        'glide-w'   => '400',
        'glide-h'   => '300',
        'glide-fit' => 'crop',
        'glide-q'   => '90',
        'class'     => 'image-class',
        'id'        => 'test-id',
    ]);

    $glideAttrs = $component->glideAttributes();

    expect($glideAttrs->toArray())->toBe([
        'w'   => '400',
        'h'   => '300',
        'fit' => 'crop',
        'q'   => '90',
    ]);
});

it('returns empty collection when no glide attributes present', function () {
    $component = createTestComponent('test.jpg', [
        'class' => 'image-class',
        'alt'   => 'Alt text',
    ]);

    $glideAttrs = $component->glideAttributes();
    expect($glideAttrs->toArray())->toBe([]);
});

it('returns null dimensions for non-existent files', function () {
    $component = createTestComponent('non-existent.jpg');
    expect($component->dimensions())->toBeNull();
});

it('returns null width and height for non-existent files', function () {
    $component = createTestComponent('non-existent.jpg');
    expect($component->width())->toBeNull();
    expect($component->height())->toBeNull();
});

it('caches dimensions after first call', function () {
    // Create a test image file
    $testImagePath = __DIR__ . '/../fixtures/test-cache.jpg';
    @mkdir(dirname($testImagePath), 0755, true);

    // Create a simple 1x1 GIF (valid image data)
    $imageData = base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=');
    file_put_contents($testImagePath, $imageData);

    $component = createTestComponent('test-cache.jpg');

    // First call should read from filesystem
    $dims1 = $component->dimensions();

    // Second call should return cached result
    $dims2 = $component->dimensions();

    expect($dims1)->toBe($dims2);
    expect($dims1)->not->toBeNull();

    // Cleanup
    @unlink($testImagePath);
});

it('calculates transformed dimensions with width only', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        ['glide-w' => '50'],
        ['width'   => 100, 'height' => 200]
    );

    $transformed = $component->transformedDimensions();

    expect($transformed['width'])->toBe(50);
    expect($transformed['height'])->toBe(100); // 200 * (50/100)
});

it('calculates transformed dimensions with height only', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        ['glide-h' => '100'],
        ['width'   => 200, 'height' => 400]
    );

    $transformed = $component->transformedDimensions();

    expect($transformed['width'])->toBe(50); // 200 * (100/400)
    expect($transformed['height'])->toBe(100);
});

it('calculates transformed dimensions with both width and height for crop fit', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        [
            'glide-w'   => '300',
            'glide-h'   => '200',
            'glide-fit' => 'crop',
        ],
        ['width' => 400, 'height' => 600]
    );

    $transformed = $component->transformedDimensions();

    // Crop fit should return exact dimensions
    expect($transformed['width'])->toBe(300);
    expect($transformed['height'])->toBe(200);
});

it('calculates transformed dimensions with both width and height for contain fit', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        [
            'glide-w'   => '300',
            'glide-h'   => '200',
            'glide-fit' => 'contain',
        ],
        ['width' => 400, 'height' => 600]
    );

    $transformed = $component->transformedDimensions();

    // Contain fit should preserve aspect ratio
    // Scale should be min(300/400, 200/600) = min(0.75, 0.333) = 0.333
    expect($transformed['width'])->toBe(133); // 400 * 0.333 rounded
    expect($transformed['height'])->toBe(200); // 600 * 0.333 rounded
});

it('applies device pixel ratio correctly', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        [
            'glide-w'   => '100',
            'glide-dpr' => '2',
        ],
        ['width' => 200, 'height' => 200]
    );

    $transformed = $component->transformedDimensions();

    expect($transformed['width'])->toBe(200); // 100 * 2
    expect($transformed['height'])->toBe(200); // 100 * 2
});

it('handles invalid or zero dimensions gracefully', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        [
            'glide-w' => '0',
            'glide-h' => '0',
        ],
        ['width' => 100, 'height' => 100]
    );

    $transformed = $component->transformedDimensions();

    // Should fallback to original dimensions when invalid values provided
    expect($transformed['width'])->toBe(100);
    expect($transformed['height'])->toBe(100);
});

it('handles different fit modes correctly', function () {
    $testCases = [
        'crop'    => true,    // fill-like
        'fill'    => true,    // fill-like
        'stretch' => true, // fill-like
        'contain' => false, // contain-like
        'max'     => false,     // contain-like
        'unknown' => false, // contain-like (default)
    ];

    foreach ($testCases as $fit => $shouldBeFillLike) {
        $component = createMockedDimensionsComponent(
            'test.jpg',
            [
                'glide-w'   => '300',
                'glide-h'   => '200',
                'glide-fit' => $fit,
            ],
            ['width' => 400, 'height' => 600]
        );

        $transformed = $component->transformedDimensions();

        if ($shouldBeFillLike) {
            // Should return exact dimensions
            expect($transformed['width'])->toBe(300, "Failed for fit: {$fit}");
            expect($transformed['height'])->toBe(200, "Failed for fit: {$fit}");
        } else {
            // Should preserve aspect ratio
            expect($transformed['width'])->toBeLessThanOrEqual(300, "Failed for fit: {$fit}");
            expect($transformed['height'])->toBeLessThanOrEqual(200, "Failed for fit: {$fit}");
        }
    }
});

it('ensures minimum dimensions of 1', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        [
            'glide-w' => '1',
            'glide-h' => '1',
        ],
        ['width' => 1000, 'height' => 1000]
    );

    $transformed = $component->transformedDimensions();

    expect($transformed['width'])->toBeGreaterThanOrEqual(1);
    expect($transformed['height'])->toBeGreaterThanOrEqual(1);
});

it('returns null transformed dimensions when original dimensions are null', function () {
    $component = createTestComponent('non-existent.jpg');

    expect($component->transformedDimensions())->toBeNull();
});

it('returns null transformed dimensions when original dimensions are invalid', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        [],
        ['width' => 0, 'height' => 0]
    );

    expect($component->transformedDimensions())->toBeNull();
});

it('width() method returns transformed width', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        ['glide-w' => '200'],
        ['width'   => 300, 'height' => 300]
    );

    expect($component->width())->toBe(200);
});

it('height() method returns transformed height', function () {
    $component = createMockedDimensionsComponent(
        'test.jpg',
        ['glide-h' => '150'],
        ['width'   => 300, 'height' => 300]
    );

    expect($component->height())->toBe(150);
});
