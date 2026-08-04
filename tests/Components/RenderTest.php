<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    config()->set('glider.source', __DIR__ . '/../fixtures');
    config()->set('glider.secure', false);
});

it('renders x-glider-img with url, dimensions, and passthrough attributes', function () {
    $html = Blade::render('<x-glider-img src="test-tiny.jpg" glide-w="10" alt="Tiny" class="w-full" loading="lazy" />');

    expect($html)->toContain('src="')
        ->toContain('/img/test-tiny~')
        ->toContain('alt="Tiny"')
        ->toContain('class="w-full"')
        ->toContain('loading="lazy"')
        ->toContain('width="10"')
        ->toMatch('/height="\d+"/')
        ->not->toContain('glide-w');
});

it('renders x-glider-img focal-point as object-position style', function () {
    $html = Blade::render('<x-glider-img src="test-tiny.jpg" glide-w="10" focal-point="top-right" />');

    expect($html)->toContain('object-fit: cover')
        ->toContain('object-position: 100% 0%')
        ->not->toContain('focal-point=');
});

it('renders x-glider-img-responsive with srcset and sizes bootstrap', function () {
    $html = Blade::render('<x-glider-img-responsive src="test-tiny.jpg" srcset-widths="5,10" alt="R" />');

    expect($html)->toContain('srcset="')
        ->toContain('5w')
        ->toContain('10w')
        ->toContain('alt="R"')
        ->not->toContain('srcset-widths=');
});

it('renders x-glider-bg with scoped style block, class, and slot', function () {
    $html = Blade::render('<x-glider-bg src="test-tiny.jpg" glide-w="10" class="hero"><h1>Hi</h1></x-glider-bg>');

    expect($html)->toContain('<style>')
        ->toMatch('/\.glide-bg-comp-[\w-]+ \{ background-image: url\(/')
        ->toContain('background-size: cover')
        ->toContain('<h1>Hi</h1>')
        ->toMatch('/class="[^"]*hero[^"]*"/')
        ->toContain('data-glide-bg')
        ->not->toContain('glide-w=');
});

it('renders x-glider-bg position, size, repeat, attachment, and focal-point props', function () {
    $html = Blade::render('<x-glider-bg src="test-tiny.jpg" size="contain" repeat="repeat-x" attachment="fixed" focal-point="25,75">x</x-glider-bg>');

    expect($html)->toContain('background-size: contain')
        ->toContain('background-repeat: repeat-x')
        ->toContain('background-attachment: fixed')
        ->toContain('background-position: 25% 75%');
});

it('renders x-glider-bg lazy attributes and fallback style', function () {
    $html = Blade::render('<x-glider-bg src="test-tiny.jpg" :lazy="true" fallback="test-tiny.jpg">x</x-glider-bg>');

    expect($html)->toContain('data-bg-lazy="true"')
        ->toContain('data-bg-src=')
        ->toContain('background-image: url(');
});

it('renders x-glider-bg-responsive media queries from explicit breakpoints', function () {
    $html = Blade::render('<x-glider-bg-responsive :breakpoints="[\'xs\' => [\'w\' => 5], \'lg\' => [\'w\' => 10]]" src="test-tiny.jpg">x</x-glider-bg-responsive>');

    expect($html)->toContain('<style>')
        ->toContain('@media (min-width: 992px)')
        ->toMatch('/\.glide-bg-comp-[\w-]+/')
        ->toContain('background-image: url(');
});

it('renders x-glider-bg-responsive from a background preset', function () {
    config()->set('glider.background_presets.tiny', [
        'xs' => ['w' => 5],
        'md' => ['w' => 10],
    ]);

    $html = Blade::render('<x-glider-bg-responsive src="test-tiny.jpg" preset="tiny">x</x-glider-bg-responsive>');

    expect($html)->toContain('@media (min-width: 768px)');
});
