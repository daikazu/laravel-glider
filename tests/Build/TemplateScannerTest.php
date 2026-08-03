<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Build\TemplateScanner;

it('finds all literal usages with their attributes', function () {
    $result = (new TemplateScanner)->scan([__DIR__ . '/../fixtures/views']);
    $bySrc = collect($result['usages'])->keyBy('src');
    expect($bySrc)->toHaveCount(5)
        ->and($bySrc['hero.jpg']->component)->toBe('img')
        ->and($bySrc['hero.jpg']->attributes)->toBe(['glide-w' => '1200', 'glide-fit' => 'crop'])
        ->and($bySrc['gallery/photo.jpg']->attributes['srcset-widths'])->toBe('400,800')
        ->and($bySrc['inline.jpg']->component)->toBe('url')
        ->and($bySrc['inline.jpg']->attributes)->toBe(['w' => '400', 'fm' => 'webp']);
});

it('reports dynamic usages separately', function () {
    $result = (new TemplateScanner)->scan([__DIR__ . '/../fixtures/views']);
    expect($result['dynamic'])->toHaveCount(3)
        ->and($result['usages'])->not->toContain(fn ($u) => str_contains($u->src, '$'));
});
