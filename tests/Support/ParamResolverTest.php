<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\ParamResolver;

it('normalizes params: merges defaults, stringifies, sorts, strips s and p', function () {
    // Isolate from the package's shipped default presets (which define a
    // 'thumbnail' preset) so passing p => 'thumbnail' below doesn't trigger
    // preset expansion — this test only exercises defaults merging.
    config()->set('glider.presets', []);
    config()->set('glider.defaults', ['fm' => 'webp', 'q' => 85]);
    $resolver = app(ParamResolver::class);
    $result = $resolver->normalize(['w' => 300, 's' => 'sig', 'p' => 'thumbnail']);
    expect($result)->toBe(['fm' => 'webp', 'q' => '85', 'w' => '300'])
        ->and(array_keys($result))->toBe(['fm', 'q', 'w']);
});

it('maps the preset alias to p', function () {
    expect(app(ParamResolver::class)->mapPresetAlias(['preset' => 'hero']))->toBe(['p' => 'hero']);
});

it('derives extension from fm, mapping pjpg to jpg', function () {
    $resolver = app(ParamResolver::class);
    expect($resolver->routeParams('photo.png', ['fm' => 'pjpg'])['extension'])->toBe('jpg');
});
