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

    public function decodeParams(string $string): array
    {
        return $this->codec->decodeParams($string);
    }

    /**
     * @throws InvalidArgumentException if the decoded path fails security
     *                                  validation (e.g. directory traversal, null bytes).
     */
    public function decodePath(string $string): string
    {
        $decoded = $this->codec->decode($string);
        if ($decoded === null) {
            return '';
        }

        // Validate decoded path for security
        if (! Str::isUrl($decoded)) {
            $this->pathValidator->validate($decoded);
        }

        return $decoded;
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
