<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Components\Img;
use Daikazu\LaravelGlider\Facades\Glider;
use Illuminate\View\ComponentAttributeBag;
use Mockery as m;

beforeEach(function () {
    config(['glider.source' => __DIR__ . '/../fixtures']);
});

afterEach(function () {
    m::close();
});

function createImg(string $src, array $attributes = []): Img
{
    $component = new Img($src);
    $component->attributes = new ComponentAttributeBag($attributes);

    return $component;
}

it('can be instantiated with a source path', function () {
    $component = createImg('test-image.jpg');
    expect($component->src)->toBe('test-image.jpg');
});

it('returns the img view when render is called', function () {
    $component = createImg('test.jpg');
    $view = $component->render();
    expect($view->getName())->toBe('glider::components.img');
});

it('generates URLs using the Glide facade in src()', function () {
    $originalInstance = Glider::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->with('test-image.jpg', [])
        ->andReturn('http://example.com/img/abc123/def456.jpg');

    Glider::swap($mockService);

    $component = createImg('test-image.jpg');
    $result = $component->src();

    expect($result)->toBe('http://example.com/img/abc123/def456.jpg');

    Glider::swap($originalInstance);
});

it('passes glide-* attributes to the Glide facade', function () {
    $originalInstance = Glider::getFacadeRoot();

    $mockService = m::mock();
    $mockService->shouldReceive('getUrl')
        ->once()
        ->with('test.jpg', ['w' => '300', 'h' => '200', 'q' => '85'])
        ->andReturn('http://example.com/img/processed.jpg');

    Glider::swap($mockService);

    $component = createImg('test.jpg', [
        'glide-w' => '300',
        'glide-h' => '200',
        'glide-q' => '85',
        'class'   => 'some-class', // non-glide attribute should be ignored
    ]);

    $component->src();

    Glider::swap($originalInstance);
});

it('returns null width and height for non-existent files', function () {
    $component = createImg('non-existent.jpg');
    expect($component->width())->toBeNull();
    expect($component->height())->toBeNull();
});

it('resolves width() and height() from real intrinsic dimensions', function () {
    $testImagePath = __DIR__ . '/../fixtures/img-test-dims.png';
    @mkdir(dirname($testImagePath), 0755, true);

    $image = imagecreate(300, 300);
    imagecolorallocate($image, 255, 255, 255);
    imagepng($image, $testImagePath);
    imagedestroy($image);

    $component = createImg('img-test-dims.png', ['glide-w' => '200']);

    expect($component->width())->toBe(200);
    expect($component->height())->toBe(200); // proportional to a 300x300 source

    @unlink($testImagePath);
});

it('returns null object-position when no focal-point attribute is present', function () {
    $component = createImg('test.jpg');
    expect($component->objectPosition())->toBeNull();
});

it('parses the focal-point attribute into a CSS object-position value', function () {
    $component = createImg('test.jpg', ['focal-point' => 'top-right']);
    expect($component->objectPosition())->toBe('100% 0%');
});

it('returns null object-position for an invalid focal-point value', function () {
    $component = createImg('test.jpg', ['focal-point' => '150,50']);
    expect($component->objectPosition())->toBeNull();
});
