<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Security\PresetPolicy;
use League\Glide\Server;

beforeEach(fn () => config()->set('glider.presets', ['thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop', 'q' => 90]]));

it('allows empty and defaults-only params', function () {
    expect(app(PresetPolicy::class)->allows([]))->toBeTrue()
        ->and(app(PresetPolicy::class)->allows(['fm' => 'webp']))->toBeTrue();
});

it('allows exact preset expansions and rejects ad-hoc params', function () {
    $policy = app(PresetPolicy::class);
    $thumbnail = app(Server::class)->getAllParams(['p' => 'thumbnail']);
    expect($policy->allows($thumbnail))->toBeTrue()
        ->and($policy->allows(['w' => 9999]))->toBeFalse();
});
