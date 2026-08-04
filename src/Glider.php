<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider;

use Daikazu\LaravelGlider\Security\PathValidator;
use Daikazu\LaravelGlider\Support\BackgroundCss;
use Daikazu\LaravelGlider\Support\PathCodec;
use Daikazu\LaravelGlider\Support\SourceResolver;
use Daikazu\LaravelGlider\Support\UrlGenerator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;

/**
 * Composition root for image URL generation. Delegates all real work to
 * its collaborators — this class exists to preserve the public facade API.
 */
final readonly class Glider
{
    public function __construct(
        private UrlGenerator $urls,
        private PathCodec $codec,
        private PathValidator $pathValidator,
        private SourceResolver $sources,
        private BackgroundCss $backgroundCss,
    ) {}

    /**
     * Parse a glider-relative URL path ({dirs...}/{name}~{token}.{ext}) into
     * its source path, manipulation params, and output extension. Returns
     * null when the path is malformed.
     *
     * @return array{path: string, params: array<string, string>, extension: string}|null
     *
     * @throws InvalidArgumentException if the parsed source path fails security
     *                                  validation (e.g. directory traversal, null bytes).
     */
    public function parsePath(string $relative): ?array
    {
        $parsed = $this->codec->parseRelativePath($relative);

        if ($parsed === null) {
            return null;
        }

        if (! Str::isUrl($parsed['path'])) {
            $this->pathValidator->validate($parsed['path']);
        }

        return $parsed;
    }

    public function getCachePath(string $path, array $params = []): string
    {
        return $this->urls->cachePath($path, $params);
    }

    public function getSourceFilesystem(string $path): FilesystemOperator
    {
        return $this->sources->filesystemFor($path);
    }

    /**
     * Get the image path to use with the filesystem adapter
     * For URLs, returns the path+query portion
     * For local paths, returns the path as-is
     */
    public function getImagePath(string $path): string
    {
        return $this->sources->imagePath($path);
    }

    public function getUrl(string $path, array $params = []): string
    {
        return $this->urls->url($path, $params);
    }

    /**
     * Alias for getUrl()
     */
    public function url(string $path, array $params = []): string
    {
        return $this->getUrl($path, $params);
    }

    /**
     * Generate responsive background URLs for multiple breakpoints
     */
    public function getResponsiveBackgroundUrls(string $path, array $breakpoints = [], array $baseParams = []): array
    {
        return $this->backgroundCss->responsiveUrls($path, $breakpoints, $baseParams);
    }

    /**
     * Get background preset configuration
     */
    public function getBackgroundPreset(string $presetName): array
    {
        return $this->backgroundCss->preset($presetName);
    }

    /**
     * Generate CSS for responsive background images
     */
    public function generateBackgroundCSS(string $path, array $breakpoints, string $selector, array $options = []): string
    {
        return $this->backgroundCss->generate($path, $breakpoints, $selector, $options);
    }
}
