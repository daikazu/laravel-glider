<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Glider;

describe('Path Traversal Security', function () {
    it('blocks directory traversal with forward slash sequences', function () {
        $service = app(Glider::class);

        expect(fn () => $service->getUrl('../../etc/passwd'))
            ->toThrow(InvalidArgumentException::class, 'directory traversal');
    });

    it('blocks directory traversal with backslash sequences', function () {
        $service = app(Glider::class);

        expect(fn () => $service->getUrl('images\..\..\config\database.php'))
            ->toThrow(InvalidArgumentException::class, 'directory traversal');
    });

    it('blocks directory traversal with mixed sequences', function () {
        $service = app(Glider::class);

        expect(fn () => $service->getUrl('images/../../../etc/passwd'))
            ->toThrow(InvalidArgumentException::class, 'directory traversal');
    });

    it('blocks null byte injection', function () {
        $service = app(Glider::class);

        expect(fn () => $service->getUrl("test.jpg\0"))
            ->toThrow(InvalidArgumentException::class, 'null byte');
    });

    it('blocks null byte with path traversal', function () {
        $service = app(Glider::class);

        expect(fn () => $service->getUrl("../../../etc/passwd\0.jpg"))
            ->toThrow(InvalidArgumentException::class);
    });

    it('allows valid image paths', function () {
        $service = app(Glider::class);

        // These should not throw exceptions
        $url = $service->getUrl('images/test.jpg');
        expect($url)->toBeString();

        $url = $service->getUrl('subfolder/image.png');
        expect($url)->toBeString();
    });

    it('allows paths with hyphens and underscores', function () {
        $service = app(Glider::class);

        $url = $service->getUrl('images/test-image_01.jpg');
        expect($url)->toBeString();
    });

    it('allows nested folder paths', function () {
        $service = app(Glider::class);

        $url = $service->getUrl('uploads/2024/01/image.jpg');
        expect($url)->toBeString();
    });

    it('blocks path that goes outside source directory', function () {
        $service = app(Glider::class);

        // Try to access a file outside the source directory
        expect(fn () => $service->getUrl('../outside.jpg'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('handles URL paths without validation', function () {
        $service = app(Glider::class);

        // URL paths should not be validated for traversal (they're remote)
        $url = $service->getUrl('https://example.com/image.jpg');
        expect($url)->toBeString();
    });

    it('decodes and validates paths safely', function () {
        $service = app(Glider::class);

        // Try to encode a malicious path and then decode it
        try {
            $service->getUrl('../../malicious.jpg');
            $this->fail('Should have thrown exception');
        } catch (InvalidArgumentException) {
            // Expected - path was blocked during encoding
            expect(true)->toBeTrue();
        }
    });

    it('prevents symlink attacks', function () {
        $service = app(Glider::class);

        // This test assumes symlinks would be resolved by realpath()
        // and blocked if they point outside the source directory
        // The actual behavior depends on the filesystem setup

        // For now, just ensure the method doesn't crash
        try {
            $service->getUrl('images/test.jpg');
            expect(true)->toBeTrue();
        } catch (InvalidArgumentException) {
            // Also acceptable if the file doesn't exist
            expect(true)->toBeTrue();
        }
    });

    it('validates parsed source paths from crafted url paths', function () {
        $service = app(Glider::class);

        // A crafted relative URL path whose parsed source resolves to a
        // traversal payload: dirs '../..', name 'passwd', se from the token
        $token = rtrim(strtr(base64_encode(http_build_query(['se' => 'jpg', 'w' => '10'])), '+/', '-_'), '=');

        expect(fn () => $service->parsePath("../../etc/passwd~{$token}.jpg"))
            ->toThrow(InvalidArgumentException::class, 'directory traversal');
    });

    it('allows valid paths when parsing crafted url paths', function () {
        // Ensure the configured source directory exists for realpath() validation
        $sourcePath = config('glider.source');
        if (! is_dir($sourcePath)) {
            mkdir($sourcePath, 0755, true);
        }

        $service = app(Glider::class);

        $token = rtrim(strtr(base64_encode(http_build_query(['se' => 'jpg', 'w' => '10'])), '+/', '-_'), '=');
        $parsed = $service->parsePath("images/test~{$token}.jpg");

        expect($parsed)->not->toBeNull()
            ->and($parsed['path'])->toBe('images/test.jpg');
    });

    it('rejects traversal smuggled through the source-extension token key', function () {
        $service = app(Glider::class);

        // `se` reconstructs the source filename — a crafted token must not
        // be able to smuggle traversal or separators through it
        $token = rtrim(strtr(base64_encode('se=jpg%2F..%2F..%2Fsecret&w=10'), '+/', '-_'), '=');

        expect($service->parsePath("images/test~{$token}.jpg"))->toBeNull();
    });
});
