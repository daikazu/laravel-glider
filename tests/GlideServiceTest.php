<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\GlideService;

beforeEach(function () {
    config([
        'laravel-glider.source'             => __DIR__ . '/fixtures',
        'laravel-glider.cache'              => sys_get_temp_dir() . '/glide-cache',
        'laravel-glider.base_url'           => 'img',
        'laravel-glider.sign_key'           => 'test-key',
        'laravel-glider.background_presets' => [
            'hero' => [
                'xs' => ['w' => 768, 'h' => 400],
                'lg' => ['w' => 1440, 'h' => 600],
            ],
        ],
    ]);
});

test('it decodes params correctly', function () {
    $service = new GlideService;
    $params = ['w' => 400, 'h' => 300, 'q' => 85];

    // Encode params
    $json = json_encode($params);
    $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

    // Decode and verify
    $decoded = $service->decodeParams($encoded);

    expect($decoded)->toBe($params);
});

test('it returns empty array for invalid params', function () {
    $service = new GlideService;

    expect($service->decodeParams('invalid-base64'))->toBe([]);
});

test('it decodes path correctly', function () {
    $service = new GlideService;
    $path = 'images/photo.jpg';

    // Encode path
    $encoded = rtrim(strtr(base64_encode($path), '+/', '-_'), '=');

    // Decode and verify
    $decoded = $service->decodePath($encoded);

    expect($decoded)->toBe($path);
});

test('it returns empty string for invalid path', function () {
    $service = new GlideService;

    expect($service->decodePath('!!!invalid!!!'))->toBe('');
});

test('it gets local filesystem for local paths', function () {
    $service = new GlideService;
    $filesystem = $service->getSourceFilesystem('local/image.jpg');

    expect($filesystem)->toBeInstanceOf(\League\Flysystem\Filesystem::class);
});

test('it gets HTTP filesystem for URLs', function () {
    $service = new GlideService;
    $filesystem = $service->getSourceFilesystem('https://example.com/image.jpg');

    expect($filesystem)->toBeInstanceOf(\League\Flysystem\Filesystem::class);
});

test('it handles URLs with ports', function () {
    $service = new GlideService;
    $filesystem = $service->getSourceFilesystem('https://example.com:8080/image.jpg');

    expect($filesystem)->toBeInstanceOf(\League\Flysystem\Filesystem::class);
});

test('it handles URLs with base paths', function () {
    $service = new GlideService;
    $filesystem = $service->getSourceFilesystem('https://cdn.example.com/images/photo.jpg');

    expect($filesystem)->toBeInstanceOf(\League\Flysystem\Filesystem::class);
});

test('it processes remote URLs even with no explicit params', function () {
    $service = new GlideService;
    $url = 'https://example.com/image.jpg';

    // With config defaults, remote URLs should be processed, not returned directly
    $result = $service->getUrl($url, []);
    expect($result)->toContain('/img/'); // Should generate a Glide route
    expect($result)->not->toBe($url); // Should not be the original URL
});

test('it removes signature param from params', function () {
    $service = new GlideService;
    $url = $service->getUrl('image.jpg', ['w' => 400, 's' => 'should-be-removed']);

    expect($url)->not->toContain('should-be-removed');
});

test('url() is an alias for getUrl()', function () {
    $service = new GlideService;
    $path = 'image.jpg';
    $params = ['w' => 400, 'h' => 300, 'q' => 85];

    expect($service->url($path, $params))->toBe($service->getUrl($path, $params));
});

test('it generates responsive background URLs', function () {
    $service = new GlideService;
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
    $service = new GlideService;
    $breakpoints = ['xs' => ['w' => 480]];
    $baseParams = ['q' => 90, 'fm' => 'webp'];

    $urls = $service->getResponsiveBackgroundUrls('hero.jpg', $breakpoints, $baseParams);

    expect($urls['xs']['params'])->toBe(['q' => 90, 'fm' => 'webp', 'w' => 480]);
});

test('it gets background preset from config', function () {
    $service = new GlideService;
    $preset = $service->getBackgroundPreset('hero');

    expect($preset)->toBe([
        'xs' => ['w' => 768, 'h' => 400],
        'lg' => ['w' => 1440, 'h' => 600],
    ]);
});

test('it throws exception for non-existent preset', function () {
    $service = new GlideService;
    $service->getBackgroundPreset('non-existent');
})->throws(InvalidArgumentException::class, "Background preset 'non-existent' not found");

test('it generates background CSS correctly', function () {
    $service = new GlideService;
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
    $service = new GlideService;
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
    $service = new GlideService;
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
    $service = new GlideService;
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

test('it encodes and decodes paths symmetrically', function () {
    $service = new GlideService;
    $originalPath = 'images/subfolder/photo.jpg';

    // Use reflection to access private method
    $reflection = new ReflectionClass($service);
    $encodeMethod = $reflection->getMethod('encodePath');
    $encodeMethod->setAccessible(true);

    $encoded = $encodeMethod->invoke($service, $originalPath);
    $decoded = $service->decodePath($encoded);

    expect($decoded)->toBe($originalPath);
});

test('it removes query parameters from path when encoding', function () {
    $service = new GlideService;
    $pathWithQuery = 'images/photo.jpg?version=123';

    // Use reflection to access private method
    $reflection = new ReflectionClass($service);
    $encodeMethod = $reflection->getMethod('encodePath');
    $encodeMethod->setAccessible(true);

    $encoded = $encodeMethod->invoke($service, $pathWithQuery);
    $decoded = $service->decodePath($encoded);

    expect($decoded)->toBe('images/photo.jpg');
});

test('it encodes and decodes params symmetrically', function () {
    $service = new GlideService;
    $originalParams = ['w' => 400, 'h' => 300, 'fit' => 'crop', 'q' => 85];

    // Use reflection to access private method
    $reflection = new ReflectionClass($service);
    $encodeMethod = $reflection->getMethod('encodeParams');
    $encodeMethod->setAccessible(true);

    $encoded = $encodeMethod->invoke($service, $originalParams);
    $decoded = $service->decodeParams($encoded);

    // encodeParams merges with server defaults, so we need to expect those defaults
    // Params are converted to strings during encoding
    $expectedParams = array_map('strval', $originalParams);
    $expectedParams['fm'] = 'webp'; // Default from config
    ksort($expectedParams);

    expect($decoded)->toBe($expectedParams);
});

test('it removes signature and p params when encoding', function () {
    $service = new GlideService;
    $params = ['w' => 400, 's' => 'signature', 'p' => 'preset'];

    // Use reflection to access private method
    $reflection = new ReflectionClass($service);
    $encodeMethod = $reflection->getMethod('encodeParams');
    $encodeMethod->setAccessible(true);

    $encoded = $encodeMethod->invoke($service, $params);
    $decoded = $service->decodeParams($encoded);

    expect($decoded)->not->toHaveKey('s')
        ->and($decoded)->not->toHaveKey('p')
        ->and($decoded)->toHaveKey('w');
});

test('it does not add signature when secure is false', function () {
    config(['laravel-glider.secure' => false]);

    $service = new GlideService;
    $url = $service->getUrl('test.jpg', ['w' => 400]);

    expect($url)->not->toContain('?s=')
        ->and($url)->not->toContain('&s=');
});

test('it adds signature when secure is true', function () {
    config(['laravel-glider.secure' => true]);

    $service = new GlideService;
    $url = $service->getUrl('test.jpg', ['w' => 400]);

    expect($url)->toContain('?s=');
});

test('it encodes and decodes paths with accented characters', function () {
    $service = new GlideService;

    $testPaths = [
        'café-image.jpg',
        'ñoño.jpg',
        'über-foto.jpg',
        'naïve.jpg',
        'images/résumé.jpg',
    ];

    foreach ($testPaths as $originalPath) {
        // Use reflection to access private method
        $reflection = new ReflectionClass($service);
        $encodeMethod = $reflection->getMethod('encodePath');
        $encodeMethod->setAccessible(true);

        $encoded = $encodeMethod->invoke($service, $originalPath);
        $decoded = $service->decodePath($encoded);

        expect($decoded)->toBe($originalPath, "Failed for path: {$originalPath}");
    }
});

test('it encodes and decodes paths with apostrophes', function () {
    $service = new GlideService;
    $originalPath = "l'apostrophe.jpg";

    // Use reflection to access private method
    $reflection = new ReflectionClass($service);
    $encodeMethod = $reflection->getMethod('encodePath');
    $encodeMethod->setAccessible(true);

    $encoded = $encodeMethod->invoke($service, $originalPath);
    $decoded = $service->decodePath($encoded);

    expect($decoded)->toBe($originalPath);
});

test('it generates valid URLs for files with accented characters', function () {
    config(['laravel-glider.secure' => false]);

    $service = new GlideService;
    $url = $service->getUrl('café-image.jpg', ['w' => 400]);

    expect($url)->toContain('/img/');

    // Extract encoded path from URL and verify it decodes correctly
    preg_match('#/img/([^/]+)/#', $url, $matches);
    expect($matches)->toHaveCount(2);

    $decoded = $service->decodePath($matches[1]);
    expect($decoded)->toBe('café-image.jpg');
});

test('it generates valid URLs for files with apostrophes', function () {
    config(['laravel-glider.secure' => false]);

    $service = new GlideService;
    $url = $service->getUrl("l'apostrophe.jpg", ['w' => 400]);

    expect($url)->toContain('/img/');

    // Extract encoded path from URL and verify it decodes correctly
    preg_match('#/img/([^/]+)/#', $url, $matches);
    expect($matches)->toHaveCount(2);

    $decoded = $service->decodePath($matches[1]);
    expect($decoded)->toBe("l'apostrophe.jpg");
});

test('it can serve image with accented characters via HTTP', function () {
    $this->withoutExceptionHandling();

    config(['laravel-glider.source' => __DIR__ . '/fixtures']);

    $service = new GlideService;
    $url = $service->getUrl('café-image.jpg', ['w' => 100]);

    $response = $this->get($url);
    $response->assertStatus(200);
});

test('it can serve image with apostrophe via HTTP', function () {
    $this->withoutExceptionHandling();

    config(['laravel-glider.source' => __DIR__ . '/fixtures']);

    $service = new GlideService;
    $url = $service->getUrl("l'apostrophe.jpg", ['w' => 100]);

    $response = $this->get($url);
    $response->assertStatus(200);
});

test('it can serve image with ñ character via HTTP', function () {
    $this->withoutExceptionHandling();

    config(['laravel-glider.source' => __DIR__ . '/fixtures']);

    $service = new GlideService;
    $url = $service->getUrl('ñoño.jpg', ['w' => 100]);

    $response = $this->get($url);
    $response->assertStatus(200);
});

test('it can serve regular ASCII image via HTTP', function () {
    $this->withoutExceptionHandling();

    config(['laravel-glider.source' => __DIR__ . '/fixtures']);

    $service = new GlideService;
    $url = $service->getUrl('test-tiny.jpg', ['w' => 100]);

    $response = $this->get($url);
    $response->assertStatus(200);
});

test('it removes signature from URL when manually provided in params', function () {
    config(['laravel-glider.secure' => true]);

    $service = new GlideService;
    // Even if 's' is provided in params, it should be removed and regenerated
    $url = $service->getUrl('test.jpg', ['w' => 400, 's' => 'manually-added']);

    // Should contain a signature, but not the manually added one
    expect($url)->toContain('?s=')
        ->and($url)->not->toContain('manually-added');
});

test('it maps preset parameter to p for League/Glide compatibility', function () {
    config([
        'laravel-glider.secure'  => false,
        'laravel-glider.presets' => [
            'thumb' => ['w' => 150, 'h' => 150, 'fit' => 'crop', 'q' => 90],
        ],
    ]);

    $service = new GlideService;
    // User passes 'preset' via glide-preset attribute
    $url = $service->getUrl('test.jpg', ['preset' => 'thumb']);

    // The URL should be generated (preset should be resolved by League/Glide)
    expect($url)->toContain('/img/');

    // Extract encoded params and verify preset was applied
    preg_match('#/img/[^/]+/([^.]+)\.#', $url, $matches);
    expect($matches)->toHaveCount(2);

    $decoded = $service->decodeParams($matches[1]);

    // The preset params should be in the encoded params
    expect($decoded)->toHaveKey('w')
        ->and($decoded['w'])->toBe('150')
        ->and($decoded)->toHaveKey('h')
        ->and($decoded['h'])->toBe('150')
        ->and($decoded)->toHaveKey('fit')
        ->and($decoded['fit'])->toBe('crop');
});

test('preset parameters can be overridden by explicit params', function () {
    config([
        'laravel-glider.secure'  => false,
        'laravel-glider.presets' => [
            'thumb' => ['w' => 150, 'h' => 150, 'fit' => 'crop', 'q' => 90],
        ],
    ]);

    $service = new GlideService;
    // User passes preset plus an override
    $url = $service->getUrl('test.jpg', ['preset' => 'thumb', 'w' => 200]);

    preg_match('#/img/[^/]+/([^.]+)\.#', $url, $matches);
    expect($matches)->toHaveCount(2);

    $decoded = $service->decodeParams($matches[1]);

    // The explicit w=200 should override preset's w=150
    expect($decoded['w'])->toBe('200')
        ->and($decoded['h'])->toBe('150'); // h from preset should remain
});

test('preset parameter is not included in encoded URL params', function () {
    config([
        'laravel-glider.secure'  => false,
        'laravel-glider.presets' => [
            'thumb' => ['w' => 150, 'h' => 150],
        ],
    ]);

    $service = new GlideService;
    $url = $service->getUrl('test.jpg', ['preset' => 'thumb']);

    preg_match('#/img/[^/]+/([^.]+)\.#', $url, $matches);
    expect($matches)->toHaveCount(2);

    $decoded = $service->decodeParams($matches[1]);

    // Neither 'preset' nor 'p' should be in the final encoded params
    expect($decoded)->not->toHaveKey('preset')
        ->and($decoded)->not->toHaveKey('p');
});
