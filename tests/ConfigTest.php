<?php

declare(strict_types=1);

it('builds disk-array config from GLIDER_*_DISK env vars', function () {
    $_ENV['GLIDER_CACHE_DISK'] = $_SERVER['GLIDER_CACHE_DISK'] = 's3';
    $_ENV['GLIDER_SOURCE_DISK'] = $_SERVER['GLIDER_SOURCE_DISK'] = 's3';
    $_ENV['GLIDER_SOURCE_PREFIX'] = $_SERVER['GLIDER_SOURCE_PREFIX'] = 'uploads';

    try {
        $config = require dirname(__DIR__) . '/config/glider.php';

        expect($config['cache'])->toBe(['disk' => 's3', 'prefix' => 'glider-cache'])
            ->and($config['source'])->toBe(['disk' => 's3', 'prefix' => 'uploads']);
    } finally {
        unset(
            $_ENV['GLIDER_CACHE_DISK'], $_SERVER['GLIDER_CACHE_DISK'],
            $_ENV['GLIDER_SOURCE_DISK'], $_SERVER['GLIDER_SOURCE_DISK'],
            $_ENV['GLIDER_SOURCE_PREFIX'], $_SERVER['GLIDER_SOURCE_PREFIX'],
        );
    }
});

it('keeps plain path config when no disk env vars are set', function () {
    $config = require dirname(__DIR__) . '/config/glider.php';

    expect($config['cache'])->toBeString()
        ->and($config['source'])->toBeString()
        ->and($config['watermarks'])->toBeString();
});
