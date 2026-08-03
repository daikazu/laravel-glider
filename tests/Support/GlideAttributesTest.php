<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\GlideAttributes;
use Illuminate\View\ComponentAttributeBag;

it('extracts glide-* prefixed attributes, stripping the prefix', function () {
    $bag = new ComponentAttributeBag([
        'glide-w'   => '400',
        'glide-h'   => '300',
        'glide-fit' => 'crop',
        'glide-q'   => '90',
        'class'     => 'image-class',
        'id'        => 'test-id',
    ]);

    expect(GlideAttributes::from($bag))->toBe([
        'w'   => '400',
        'h'   => '300',
        'fit' => 'crop',
        'q'   => '90',
    ]);
});

it('returns an empty array when no glide attributes are present', function () {
    $bag = new ComponentAttributeBag([
        'class' => 'image-class',
        'alt'   => 'Alt text',
    ]);

    expect(GlideAttributes::from($bag))->toBe([]);
});
