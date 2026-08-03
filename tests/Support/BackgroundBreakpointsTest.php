<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\BackgroundBreakpoints;

it('expands default breakpoints when neither preset nor custom breakpoints are given', function () {
    $result = (new BackgroundBreakpoints)->expand(null, null, []);

    expect($result->pluck('name')->all())->toBe(['xs', 'sm', 'md', 'lg', 'xl'])
        ->and($result->first())->toBe(['name' => 'xs', 'min_width' => 0, 'params' => ['w' => 480]])
        ->and($result->last())->toBe(['name' => 'xl', 'min_width' => 1200, 'params' => ['w' => 1920]]);
});

it('expands custom breakpoints, merging in the base glide attributes and sorting by min_width', function () {
    $result = (new BackgroundBreakpoints)->expand(null, [
        'lg' => ['w' => 1024],
        'xs' => ['w' => 320],
    ], ['q' => 90]);

    expect($result->pluck('name')->all())->toBe(['xs', 'lg'])
        ->and($result->first()['params'])->toBe(['q' => 90, 'w' => 320])
        ->and($result->first()['min_width'])->toBe(0)
        ->and($result->last()['min_width'])->toBe(992);
});

it('lets breakpoint-specific params override the base glide attributes', function () {
    $result = (new BackgroundBreakpoints)->expand(null, ['xs' => ['w' => 320]], ['w' => 100, 'q' => 90]);

    expect($result->first()['params'])->toBe(['w' => 320, 'q' => 90]);
});

it('resolves named breakpoints to their pixel widths', function () {
    $breakpoints = ['xs' => ['w' => 1], 'sm' => ['w' => 1], 'md' => ['w' => 1], 'lg' => ['w' => 1], 'xl' => ['w' => 1], '2xl' => ['w' => 1]];

    $result = (new BackgroundBreakpoints)->expand(null, $breakpoints, []);

    expect($result->firstWhere('name', 'xs')['min_width'])->toBe(0)
        ->and($result->firstWhere('name', 'sm')['min_width'])->toBe(576)
        ->and($result->firstWhere('name', 'md')['min_width'])->toBe(768)
        ->and($result->firstWhere('name', 'lg')['min_width'])->toBe(992)
        ->and($result->firstWhere('name', 'xl')['min_width'])->toBe(1200)
        ->and($result->firstWhere('name', '2xl')['min_width'])->toBe(1400);
});

it('treats numeric breakpoint keys as literal pixel widths', function () {
    $result = (new BackgroundBreakpoints)->expand(null, [320 => ['w' => 1], 1024 => ['w' => 1]], []);

    expect($result->pluck('min_width')->all())->toBe([320, 1024]);
});

it('expands a named preset from config', function () {
    config(['glider.background_presets' => [
        'hero' => [
            'xs' => ['w' => 768, 'h' => 400],
            'lg' => ['w' => 1440, 'h' => 600],
        ],
    ]]);

    $result = (new BackgroundBreakpoints)->expand('hero', null, []);

    expect($result->pluck('name')->all())->toBe(['xs', 'lg'])
        ->and($result->first()['params'])->toBe(['w' => 768, 'h' => 400]);
});

it('supports presets nested under a breakpoints key', function () {
    config(['glider.background_presets' => [
        'hero' => [
            'breakpoints' => [
                'xs' => ['w' => 768],
            ],
        ],
    ]]);

    $result = (new BackgroundBreakpoints)->expand('hero', null, []);

    expect($result->pluck('name')->all())->toBe(['xs']);
});

it('throws for an unknown preset', function () {
    config(['glider.background_presets' => []]);

    (new BackgroundBreakpoints)->expand('does-not-exist', null, []);
})->throws(InvalidArgumentException::class, "Background preset 'does-not-exist' not found in config");

it('throws for empty custom breakpoints array passed explicitly', function () {
    // An empty array falls through to the library defaults per the `expand()`
    // contract (only null is treated as "use defaults"); to reach the
    // "cannot be empty" guard we exercise it via a preset pointing at [].
    config(['glider.background_presets' => ['empty' => []]]);

    (new BackgroundBreakpoints)->expand('empty', null, []);
})->throws(InvalidArgumentException::class, 'Breakpoints array cannot be empty');

it('throws when a breakpoint value is not an array', function () {
    (new BackgroundBreakpoints)->expand(null, ['xs' => 'not-an-array'], []);
})->throws(InvalidArgumentException::class, "Breakpoint 'xs' must have an array of parameters");

it('returns items without url keys', function () {
    $result = (new BackgroundBreakpoints)->expand(null, ['xs' => ['w' => 100]], []);

    expect(array_keys($result->first()))->toBe(['name', 'min_width', 'params']);
});
