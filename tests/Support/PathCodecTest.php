<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\PathCodec;

it('round-trips a path through base64url', function () {
    $codec = new PathCodec;
    expect($codec->decode($codec->encode('images/héro image.jpg')))->toBe('images/héro image.jpg');
});

it('produces url-safe output with no padding', function () {
    $encoded = (new PathCodec)->encode('subject?with/slashes+and+plus');
    expect($encoded)->not->toMatch('/[+\/=]/');
});

it('returns null for invalid base64 input', function () {
    expect((new PathCodec)->decode('!!!not-base64!!!'))->toBeNull();
});

it('round-trips params and returns empty array for garbage', function () {
    $codec = new PathCodec;
    expect($codec->decodeParams($codec->encodeParams(['w' => '100'])))->toBe(['w' => '100'])
        ->and($codec->decodeParams('garbage'))->toBe([]);
});
