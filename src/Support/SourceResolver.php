<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Daikazu\LaravelGlider\Security\UrlValidator;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;

/**
 * Resolves the source filesystem and image path used to serve a given
 * `$path` — either the configured local/disk source (`glider.source`),
 * or a first-party read-only HTTP adapter for remote URLs.
 *
 * Remote URLs are validated against SSRF (`UrlValidator`) before the
 * HTTP adapter is ever constructed.
 */
final class SourceResolver
{
    public function __construct(
        private readonly FilesystemResolver $filesystems,
        private readonly UrlValidator $urls,
        private readonly Factory $http,
    ) {}

    public function filesystemFor(string $path): FilesystemOperator
    {
        if ($this->isUrl($path)) {
            // Validate URL to prevent SSRF attacks before building an adapter.
            $this->urls->validate($path);

            $parsedUrl = parse_url($path);
            if ($parsedUrl === false || ! isset($parsedUrl['scheme'], $parsedUrl['host'])) {
                throw new InvalidArgumentException("Invalid URL provided: {$path}");
            }

            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $baseUrl .= ':' . $parsedUrl['port'];
            }

            return new Filesystem(new HttpFilesystemAdapter($baseUrl, $this->http));
        }

        return $this->filesystems->resolve(config('glider.source'));
    }

    /**
     * Get the image path to use with the filesystem adapter.
     * For URLs, returns the path+query portion.
     * For local paths, returns the path as-is.
     */
    public function imagePath(string $path): string
    {
        if (! $this->isUrl($path)) {
            return $path;
        }

        $parsedUrl = parse_url($path);
        if ($parsedUrl === false) {
            return $path;
        }

        $imagePath = $parsedUrl['path'] ?? '/';
        if (isset($parsedUrl['query'])) {
            $imagePath .= '?' . $parsedUrl['query'];
        }

        return ltrim($imagePath, '/');
    }

    private function isUrl(string $path): bool
    {
        return Str::isUrl($path) || str_contains($path, '://');
    }
}
