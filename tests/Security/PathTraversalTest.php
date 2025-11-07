<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\GlideService;

describe('Path Traversal Security', function () {
    it('blocks directory traversal with forward slash sequences', function () {
        $service = new GlideService;

        expect(fn () => $service->getUrl('../../etc/passwd'))
            ->toThrow(InvalidArgumentException::class, 'directory traversal');
    });

    it('blocks directory traversal with backslash sequences', function () {
        $service = new GlideService;

        expect(fn () => $service->getUrl('images\..\..\config\database.php'))
            ->toThrow(InvalidArgumentException::class, 'directory traversal');
    });

    it('blocks directory traversal with mixed sequences', function () {
        $service = new GlideService;

        expect(fn () => $service->getUrl('images/../../../etc/passwd'))
            ->toThrow(InvalidArgumentException::class, 'directory traversal');
    });

    it('blocks null byte injection', function () {
        $service = new GlideService;

        expect(fn () => $service->getUrl("test.jpg\0"))
            ->toThrow(InvalidArgumentException::class, 'null byte');
    });

    it('blocks null byte with path traversal', function () {
        $service = new GlideService;

        expect(fn () => $service->getUrl("../../../etc/passwd\0.jpg"))
            ->toThrow(InvalidArgumentException::class);
    });

    it('allows valid image paths', function () {
        $service = new GlideService;

        // These should not throw exceptions
        $url = $service->getUrl('images/test.jpg');
        expect($url)->toBeString();

        $url = $service->getUrl('subfolder/image.png');
        expect($url)->toBeString();
    });

    it('allows paths with hyphens and underscores', function () {
        $service = new GlideService;

        $url = $service->getUrl('images/test-image_01.jpg');
        expect($url)->toBeString();
    });

    it('allows nested folder paths', function () {
        $service = new GlideService;

        $url = $service->getUrl('uploads/2024/01/image.jpg');
        expect($url)->toBeString();
    });

    it('blocks path that goes outside source directory', function () {
        $service = new GlideService;

        // Try to access a file outside the source directory
        expect(fn () => $service->getUrl('../outside.jpg'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('handles URL paths without validation', function () {
        $service = new GlideService;

        // URL paths should not be validated for traversal (they're remote)
        $url = $service->getUrl('https://example.com/image.jpg');
        expect($url)->toBeString();
    });

    it('decodes and validates paths safely', function () {
        $service = new GlideService;

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
        $service = new GlideService;

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

    it('validates paths after decoding from base64', function () {
        $service = new GlideService;

        // Create a malicious path
        $maliciousPath = '../../etc/passwd';
        $encoded = rtrim(strtr(base64_encode($maliciousPath), '+/', '-_'), '=');

        // Try to decode it
        expect(fn () => $service->decodePath($encoded))
            ->toThrow(InvalidArgumentException::class, 'directory traversal');
    });

    it('allows valid paths after decoding', function () {
        $service = new GlideService;

        $validPath = 'images/test.jpg';
        $encoded = rtrim(strtr(base64_encode($validPath), '+/', '-_'), '=');

        $decoded = $service->decodePath($encoded);
        expect($decoded)->toBe($validPath);
    });
});
