<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Facades\Glider as GliderFacade;
use Daikazu\LaravelGlider\Glider;
use Daikazu\LaravelGlider\Support\UrlGenerator;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;

beforeEach(function () {
    config([
        'glider.source'             => __DIR__ . '/fixtures',
        'glider.cache'              => sys_get_temp_dir() . '/glide-cache',
        'glider.base_url'           => 'img',
        'glider.sign_key'           => 'test-key',
        'glider.background_presets' => [
            'hero' => [
                'xs' => ['w' => 768, 'h' => 400],
                'lg' => ['w' => 1440, 'h' => 600],
            ],
        ],
    ]);
});

/**
 * Round-trip helper: parse a generated glider URL the way the controller
 * route would (prefix check + segment decode + parseRelativePath).
 */
function parseGliderUrl(string $url): ?array
{
    return app(UrlGenerator::class)->parseUrl($url);
}

test('it parses a generated url back into path, params, and extension', function () {
    config(['glider.secure' => false]);

    $parsed = parseGliderUrl(app(Glider::class)->url('images/photo.jpg', ['w' => 400, 'h' => 300]));

    expect($parsed)->not->toBeNull()
        ->and($parsed['path'])->toBe('images/photo.jpg')
        ->and($parsed['params']['w'])->toBe('400')
        ->and($parsed['params']['h'])->toBe('300')
        ->and($parsed['extension'])->toBe('webp');
});

test('parsePath returns null for malformed relative paths', function () {
    $service = app(Glider::class);

    expect($service->parsePath('no-token-here.webp'))->toBeNull()
        ->and($service->parsePath('a/b~!!!invalid!!!.webp'))->toBeNull();
});

test('urls are human readable: path and name stay visible', function () {
    config(['glider.secure' => false]);

    $url = app(Glider::class)->url('coins/themes/memorial/hero.jpg', ['w' => 333]);

    expect($url)->toContain('/img/coins/themes/memorial/hero~')
        ->and($url)->toEndWith('.webp');
});

test('it gets local filesystem for local paths', function () {
    $service = app(Glider::class);
    $filesystem = $service->getSourceFilesystem('local/image.jpg');

    expect($filesystem)->toBeInstanceOf(Filesystem::class);
});

test('it gets HTTP filesystem for URLs', function () {
    $service = app(Glider::class);
    $filesystem = $service->getSourceFilesystem('https://example.com/image.jpg');

    expect($filesystem)->toBeInstanceOf(Filesystem::class);
});

test('it handles URLs with ports', function () {
    $service = app(Glider::class);
    $filesystem = $service->getSourceFilesystem('https://example.com:8080/image.jpg');

    expect($filesystem)->toBeInstanceOf(Filesystem::class);
});

test('it handles URLs with base paths', function () {
    $service = app(Glider::class);
    $filesystem = $service->getSourceFilesystem('https://cdn.example.com/images/photo.jpg');

    expect($filesystem)->toBeInstanceOf(Filesystem::class);
});

test('it processes remote URLs even with no explicit params', function () {
    $service = app(Glider::class);
    $url = 'https://example.com/image.jpg';

    // With config defaults, remote URLs should be processed, not returned directly
    $result = $service->getUrl($url, []);
    expect($result)->toContain('/img/'); // Should generate a Glide route
    expect($result)->not->toBe($url); // Should not be the original URL
});

test('it removes signature param from params', function () {
    $service = app(Glider::class);
    $url = $service->getUrl('image.jpg', ['w' => 400, 's' => 'should-be-removed']);

    expect($url)->not->toContain('should-be-removed');
});

test('url() is an alias for getUrl()', function () {
    $service = app(Glider::class);
    $path = 'image.jpg';
    $params = ['w' => 400, 'h' => 300, 'q' => 85];

    expect($service->url($path, $params))->toBe($service->getUrl($path, $params));
});

test('it generates responsive background URLs', function () {
    $service = app(Glider::class);
    $breakpoints = [
        'xs' => ['w' => 480],
        'lg' => ['w' => 1024],
    ];

    $urls = $service->getResponsiveBackgroundUrls('hero.jpg', $breakpoints);

    expect($urls)->toHaveKeys(['xs', 'lg'])
        ->and($urls['xs'])->toHaveKeys(['url', 'params', 'min_width'])
        ->and($urls['xs']['params'])->toBe(['w' => 480])
        ->and($urls['xs']['min_width'])->toBe(0)
        ->and($urls['lg']['min_width'])->toBe(992);
});

test('it merges base params with breakpoint params', function () {
    $service = app(Glider::class);
    $breakpoints = ['xs' => ['w' => 480]];
    $baseParams = ['q' => 90, 'fm' => 'webp'];

    $urls = $service->getResponsiveBackgroundUrls('hero.jpg', $breakpoints, $baseParams);

    expect($urls['xs']['params'])->toBe(['q' => 90, 'fm' => 'webp', 'w' => 480]);
});

test('it gets background preset from config', function () {
    $service = app(Glider::class);
    $preset = $service->getBackgroundPreset('hero');

    expect($preset)->toBe([
        'xs' => ['w' => 768, 'h' => 400],
        'lg' => ['w' => 1440, 'h' => 600],
    ]);
});

test('it throws exception for non-existent preset', function () {
    $service = app(Glider::class);
    $service->getBackgroundPreset('non-existent');
})->throws(InvalidArgumentException::class, "Background preset 'non-existent' not found");

test('it generates background CSS correctly', function () {
    $service = app(Glider::class);
    $breakpoints = [
        'xs' => ['w' => 480],
        'md' => ['w' => 768],
    ];

    $css = $service->generateBackgroundCSS('hero.jpg', $breakpoints, '.hero');

    expect($css)
        ->toContain('.hero {')
        ->toContain('background-image:')
        ->toContain('background-position: center')
        ->toContain('background-size: cover')
        ->toContain('@media (min-width: 768px)');
});

test('it applies custom CSS options', function () {
    $service = app(Glider::class);
    $breakpoints = ['xs' => ['w' => 480]];
    $options = [
        'position'   => 'top left',
        'size'       => 'contain',
        'repeat'     => 'repeat',
        'attachment' => 'fixed',
    ];

    $css = $service->generateBackgroundCSS('hero.jpg', $breakpoints, '.hero', $options);

    expect($css)
        ->toContain('background-position: top left')
        ->toContain('background-size: contain')
        ->toContain('background-repeat: repeat')
        ->toContain('background-attachment: fixed');
});

test('it converts breakpoint names to pixel widths', function () {
    $service = app(Glider::class);
    $breakpoints = [
        'xs'  => ['w' => 100],
        'sm'  => ['w' => 100],
        'md'  => ['w' => 100],
        'lg'  => ['w' => 100],
        'xl'  => ['w' => 100],
        '2xl' => ['w' => 100],
    ];

    $urls = $service->getResponsiveBackgroundUrls('image.jpg', $breakpoints);

    expect($urls['xs']['min_width'])->toBe(0)
        ->and($urls['sm']['min_width'])->toBe(576)
        ->and($urls['md']['min_width'])->toBe(768)
        ->and($urls['lg']['min_width'])->toBe(992)
        ->and($urls['xl']['min_width'])->toBe(1200)
        ->and($urls['2xl']['min_width'])->toBe(1400);
});

test('it handles numeric breakpoints', function () {
    $service = app(Glider::class);
    $breakpoints = [
        320  => ['w' => 100],
        768  => ['w' => 100],
        1024 => ['w' => 100],
    ];

    $urls = $service->getResponsiveBackgroundUrls('image.jpg', $breakpoints);

    expect($urls[320]['min_width'])->toBe(320)
        ->and($urls[768]['min_width'])->toBe(768)
        ->and($urls[1024]['min_width'])->toBe(1024);
});

test('it round-trips paths through url generation and parsing', function () {
    config(['glider.secure' => false]);

    $parsed = parseGliderUrl(app(Glider::class)->url('images/subfolder/photo.jpg', ['w' => 400]));

    expect($parsed['path'])->toBe('images/subfolder/photo.jpg');
});

test('it removes query parameters from local paths when building urls', function () {
    config(['glider.secure' => false]);

    $parsed = parseGliderUrl(app(Glider::class)->url('images/photo.jpg?version=123', ['w' => 400]));

    expect($parsed['path'])->toBe('images/photo.jpg');
});

test('it round-trips params merged with server defaults', function () {
    config(['glider.secure' => false]);

    $originalParams = ['w' => 400, 'h' => 300, 'fit' => 'crop', 'q' => 85];
    $parsed = parseGliderUrl(app(Glider::class)->url('images/photo.jpg', $originalParams));
    $params = $parsed['params'];
    $params['fm'] ??= $parsed['extension'];

    // The token merges server defaults; values are stringified and sorted
    $expectedParams = array_map('strval', $originalParams);
    $expectedParams['fm'] = 'webp'; // Default from config
    ksort($expectedParams);
    ksort($params);

    expect($params)->toBe($expectedParams);
});

test('it removes signature and p params from the url token', function () {
    config(['glider.secure' => false]);

    $parsed = parseGliderUrl(app(Glider::class)->url('images/photo.jpg', ['w' => 400, 's' => 'signature', 'p' => 'preset']));

    expect($parsed['params'])->not->toHaveKey('s')
        ->and($parsed['params'])->not->toHaveKey('p')
        ->and($parsed['params'])->toHaveKey('w');
});

test('it does not add signature when secure is false', function () {
    config(['glider.secure' => false]);

    $service = app(Glider::class);
    $url = $service->getUrl('test.jpg', ['w' => 400]);

    expect($url)->not->toContain('?s=')
        ->and($url)->not->toContain('&s=');
});

test('it adds signature when secure is true', function () {
    config(['glider.secure' => true]);

    $service = app(Glider::class);
    $url = $service->getUrl('test.jpg', ['w' => 400]);

    expect($url)->toContain('?s=');
});

test('it round-trips paths with accented characters and apostrophes', function (string $originalPath) {
    config(['glider.secure' => false]);

    $parsed = parseGliderUrl(app(Glider::class)->url($originalPath, ['w' => 400]));

    expect($parsed)->not->toBeNull()
        ->and($parsed['path'])->toBe($originalPath);
})->with([
    'café-image.jpg',
    'ñoño.jpg',
    'über-foto.jpg',
    'naïve.jpg',
    'images/résumé.jpg',
    "l'apostrophe.jpg",
]);

test('it percent-encodes special characters in generated urls', function () {
    config(['glider.secure' => false]);

    $url = app(Glider::class)->getUrl('café-image.jpg', ['w' => 400]);

    expect($url)->toContain('/img/caf%C3%A9-image~');
});

test('it can serve image with accented characters via HTTP', function () {
    $this->withoutExceptionHandling();

    config(['glider.source' => __DIR__ . '/fixtures']);

    $service = app(Glider::class);
    $url = $service->getUrl('café-image.jpg', ['w' => 100]);

    $response = $this->get($url);
    $response->assertStatus(200);
});

test('it can serve image with apostrophe via HTTP', function () {
    $this->withoutExceptionHandling();

    config(['glider.source' => __DIR__ . '/fixtures']);

    $service = app(Glider::class);
    $url = $service->getUrl("l'apostrophe.jpg", ['w' => 100]);

    $response = $this->get($url);
    $response->assertStatus(200);
});

test('it can serve image with ñ character via HTTP', function () {
    $this->withoutExceptionHandling();

    config(['glider.source' => __DIR__ . '/fixtures']);

    $service = app(Glider::class);
    $url = $service->getUrl('ñoño.jpg', ['w' => 100]);

    $response = $this->get($url);
    $response->assertStatus(200);
});

test('it serves images with spaces and url-special characters via HTTP, signatures on', function (string $filename) {
    $this->withoutExceptionHandling();

    config(['glider.source' => __DIR__ . '/fixtures', 'glider.secure' => true]);

    $fixture = __DIR__ . '/fixtures/' . $filename;
    @mkdir(dirname($fixture), 0755, true);
    copy(__DIR__ . '/fixtures/test-tiny.jpg', $fixture);

    try {
        $url = app(Glider::class)->getUrl($filename, ['w' => 10]);

        $this->get($url)->assertOk();
    } finally {
        @unlink($fixture);
        if (dirname($fixture) !== __DIR__ . '/fixtures') {
            @rmdir(dirname($fixture));
        }
    }
})->with([
    'space in name'    => 'hero image (1).jpg',
    'space in dir'     => 'summer 2024/beach day.jpg',
    'percent in name'  => '50% off.jpg',
    'plus in name'     => 'a+b.jpg',
    'hash in name'     => 'img#1.jpg',
    'ampersand name'   => 'file&name.jpg',
    'brackets in name' => 'photo[1].jpg',
]);

test('it can serve regular ASCII image via HTTP', function () {
    $this->withoutExceptionHandling();

    config(['glider.source' => __DIR__ . '/fixtures']);

    $service = app(Glider::class);
    $url = $service->getUrl('test-tiny.jpg', ['w' => 100]);

    $response = $this->get($url);
    $response->assertStatus(200);
});

test('it removes signature from URL when manually provided in params', function () {
    config(['glider.secure' => true]);

    $service = app(Glider::class);
    // Even if 's' is provided in params, it should be removed and regenerated
    $url = $service->getUrl('test.jpg', ['w' => 400, 's' => 'manually-added']);

    // Should contain a signature, but not the manually added one
    expect($url)->toContain('?s=')
        ->and($url)->not->toContain('manually-added');
});

test('it maps preset parameter to p for League/Glide compatibility', function () {
    config([
        'glider.secure'  => false,
        'glider.presets' => [
            'thumb' => ['w' => 150, 'h' => 150, 'fit' => 'crop', 'q' => 90],
        ],
    ]);

    $service = app(Glider::class);
    // User passes 'preset' via glide-preset attribute
    $url = $service->getUrl('test.jpg', ['preset' => 'thumb']);

    // The URL should be generated (preset should be resolved by League/Glide)
    expect($url)->toContain('/img/');

    $decoded = parseGliderUrl($url)['params'];

    // The preset params should be in the token params
    expect($decoded)->toHaveKey('w')
        ->and($decoded['w'])->toBe('150')
        ->and($decoded)->toHaveKey('h')
        ->and($decoded['h'])->toBe('150')
        ->and($decoded)->toHaveKey('fit')
        ->and($decoded['fit'])->toBe('crop');
});

test('preset parameters can be overridden by explicit params', function () {
    config([
        'glider.secure'  => false,
        'glider.presets' => [
            'thumb' => ['w' => 150, 'h' => 150, 'fit' => 'crop', 'q' => 90],
        ],
    ]);

    $service = app(Glider::class);
    // User passes preset plus an override
    $url = $service->getUrl('test.jpg', ['preset' => 'thumb', 'w' => 200]);

    $decoded = parseGliderUrl($url)['params'];

    // The explicit w=200 should override preset's w=150
    expect($decoded['w'])->toBe('200')
        ->and($decoded['h'])->toBe('150'); // h from preset should remain
});

test('it generates a glide route url instead of throwing when source is a disk array', function () {
    // Regression coverage: the direct-serve shortcut's second branch called
    // Str::startsWith($sourceRoot, storage_path()) without guarding that
    // $sourceRoot is a string. With a disk-array source (spec-advertised,
    // e.g. 'source' => ['disk' => 's3']) and a zero-param Glider::url()
    // call, $sourceRoot is an array and that call TypeErrors instead of
    // falling through to normal route generation.
    config()->set('glider.source', ['disk' => 'assets']);
    Storage::fake('assets');

    expect(GliderFacade::url('a.jpg'))->toContain('/img/');
});

test('preset parameter is not included in encoded URL params', function () {
    config([
        'glider.secure'  => false,
        'glider.presets' => [
            'thumb' => ['w' => 150, 'h' => 150],
        ],
    ]);

    $service = app(Glider::class);
    $url = $service->getUrl('test.jpg', ['preset' => 'thumb']);

    $decoded = parseGliderUrl($url)['params'];

    // Neither 'preset' nor 'p' should be in the final token params
    expect($decoded)->not->toHaveKey('preset')
        ->and($decoded)->not->toHaveKey('p');
});
