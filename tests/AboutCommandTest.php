<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\FilesystemResolver;

it('registers a Glider section in artisan about', function () {
    $this->artisan('about')
        ->expectsOutputToContain('Glider')
        ->expectsOutputToContain('Signed URLs')
        ->expectsOutputToContain('On-the-fly')
        ->assertSuccessful();
});

it('describes a plain path cache config', function () {
    expect((new FilesystemResolver)->describe('/tmp/glider-cache'))->toBe('/tmp/glider-cache')
        ->and((new FilesystemResolver)->describe('public/img'))->toBe(base_path('public/img'));
});

it('describes a disk cache config', function () {
    $resolver = new FilesystemResolver;

    expect($resolver->describe(['disk' => 's3', 'prefix' => 'glider-cache']))->toBe("disk 's3' (prefix 'glider-cache')")
        ->and($resolver->describe(['disk' => 's3']))->toBe("disk 's3'");
});
