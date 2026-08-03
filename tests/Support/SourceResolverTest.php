<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\SourceResolver;
use Illuminate\Support\Facades\Http;
use League\Flysystem\FilesystemOperator;

beforeEach(function () {
    config(['glider.source' => __DIR__ . '/../fixtures']);
});

it('returns the configured source filesystem for local paths', function () {
    expect(app(SourceResolver::class)->filesystemFor('images/a.jpg'))
        ->toBeInstanceOf(FilesystemOperator::class);
});

it('rejects SSRF urls before building an adapter', function () {
    app(SourceResolver::class)->filesystemFor('http://127.0.0.1/x.jpg');
})->throws(InvalidArgumentException::class);

it('extracts path plus query for urls', function () {
    expect(app(SourceResolver::class)->imagePath('https://example.com/a/b.jpg?v=2'))->toBe('a/b.jpg?v=2');
});

it('leaves local paths unchanged', function () {
    expect(app(SourceResolver::class)->imagePath('images/a.jpg'))->toBe('images/a.jpg');
});

it('builds an HTTP filesystem for a remote URL from scheme+host+port', function () {
    Http::fake(['example.com:8080/*' => Http::response('BYTES')]);

    $fs = app(SourceResolver::class)->filesystemFor('https://example.com:8080/images/a.jpg');

    expect($fs)->toBeInstanceOf(FilesystemOperator::class)
        ->and($fs->read('images/a.jpg'))->toBe('BYTES');
});
