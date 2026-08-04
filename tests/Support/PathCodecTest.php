<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\PathCodec;

function codec(): PathCodec
{
    return new PathCodec;
}

it('builds a readable relative path with the name leading the filename', function () {
    $relative = codec()->buildRelativePath('coins/themes/memorial/hero.jpg', ['fm' => 'webp', 'q' => '85', 'w' => '333'], 'webp');

    expect($relative)->toStartWith('coins/themes/memorial/hero~')
        ->and($relative)->toEndWith('.webp')
        ->and($relative)->not->toContain('{');
});

it('round-trips a nested local path', function () {
    $relative = codec()->buildRelativePath('coins/themes/memorial/hero.jpg', ['fm' => 'webp', 'w' => '333'], 'webp');
    $parsed = codec()->parseRelativePath($relative);

    expect($parsed)->not->toBeNull()
        ->and($parsed['path'])->toBe('coins/themes/memorial/hero.jpg')
        ->and($parsed['params'])->toBe(['fm' => 'webp', 'w' => '333'])
        ->and($parsed['extension'])->toBe('webp');
});

it('round-trips a root-level file and one without an extension', function () {
    $withExt = codec()->parseRelativePath(codec()->buildRelativePath('hero.jpg', ['w' => '10'], 'jpg'));
    $noExt = codec()->parseRelativePath(codec()->buildRelativePath('hero', ['w' => '10'], 'jpg'));

    expect($withExt['path'])->toBe('hero.jpg')
        ->and($noExt['path'])->toBe('hero');
});

it('round-trips names containing tildes, dots, and unicode', function (string $path) {
    $parsed = codec()->parseRelativePath(codec()->buildRelativePath($path, ['w' => '10'], 'webp'));

    expect($parsed['path'])->toBe($path);
})->with([
    'tilde in name'   => 'files/my~archive.jpg',
    'dots in name'    => 'files/photo.v2.final.jpg',
    'unicode name'    => 'fixtures/café-image.jpg',
    'apostrophe name' => "fixtures/l'apostrophe.jpg",
]);

it('round-trips param values containing slashes, underscores, and hyphens', function () {
    $params = ['mark' => 'logos/tjm_logo-v2.png', 'markpos' => 'top-left', 'w' => '600'];
    $parsed = codec()->parseRelativePath(codec()->buildRelativePath('a.jpg', $params, 'webp'));

    expect($parsed['params'])->toBe($params);
});

it('round-trips a remote URL source through the token', function () {
    $relative = codec()->buildRelativePath('https://example.com/photos/team.jpg?v=2', ['w' => '400'], 'webp');
    $parsed = codec()->parseRelativePath($relative);

    expect($relative)->toStartWith('team~')
        ->and($relative)->not->toContain('/')
        ->and($parsed['path'])->toBe('https://example.com/photos/team.jpg?v=2')
        ->and($parsed['params'])->toBe(['w' => '400']);
});

it('returns null for malformed paths', function (string $relative) {
    expect(codec()->parseRelativePath($relative))->toBeNull();
})->with([
    'no token delimiter'   => 'coins/hero.webp',
    'invalid base64 token' => 'coins/hero~!!!bad!!!.webp',
    'disallowed extension' => 'coins/hero~' . rtrim(strtr(base64_encode('se=jpg&w=10'), '+/', '-_'), '=') . '.php',
    'empty token'          => 'coins/hero~.webp',
    'empty name'           => 'coins/~' . rtrim(strtr(base64_encode('se=jpg&w=10'), '+/', '-_'), '=') . '.webp',
]);

it('rejects a token that decodes to garbage', function () {
    $garbage = rtrim(strtr(base64_encode("\x00\x01binary"), '+/', '-_'), '=');

    expect(codec()->parseRelativePath('coins/hero~' . $garbage . '.webp'))->toBeNull();
});

it('produces url-safe encoded segments preserving slashes and tildes', function () {
    $encoded = codec()->encodeUrlSegments('fixtures/café images/hero~abc.webp');

    expect($encoded)->toBe('fixtures/caf%C3%A9%20images/hero~abc.webp');
});
