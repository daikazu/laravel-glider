<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Daikazu\LaravelGlider\Security\PathValidator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Glide\Signatures\SignatureInterface;

use function Illuminate\Filesystem\join_paths;

final readonly class UrlGenerator
{
    public function __construct(
        private PathCodec $codec,
        private ParamResolver $params,
        private PathValidator $pathValidator,
        private SignatureInterface $signature,
    ) {}

    public function url(string $path, array $params = []): string
    {
        // The signature is created later and should be ignored even if provided as a parameter
        unset($params['s']);

        // Map 'preset' to 'p' for League/Glide compatibility
        // Users use glide-preset="name" which becomes ['preset' => 'name']
        // But League/Glide expects ['p' => 'name'] for preset lookups
        $params = $this->params->mapPresetAlias($params);

        // Sometimes we can directly serve the image from the public disk
        // (Only for local paths, not URLs, and only when the source is a
        // plain path string — a disk-array source, e.g. ['disk' => 's3'],
        // skips this shortcut entirely and falls through to route generation)
        $sourceRoot = config('glider.source');

        if ($params === [] && ! Str::isUrl($path) && is_string($sourceRoot)) {
            $publicRoot = config('filesystems.disks.public.root');

            if (is_string($publicRoot) && Str::startsWith($sourceRoot, $publicRoot)) {
                return Storage::disk('public')->url($path);
            }

            if (Str::startsWith($sourceRoot, storage_path())) {
                return asset(join_paths(Str::after($sourceRoot, storage_path()), $path));
            }
        }

        $relativeUrl = $this->prefix() . '/' . $this->codec->encodeUrlSegments($this->relativePath($path, $params));

        // Only add signature if secure mode is enabled
        if (config('glider.secure', true)) {
            $signedParams = $this->signature->addSignature($relativeUrl, []);

            return url($relativeUrl) . '?s=' . $signedParams['s'];
        }

        return url($relativeUrl);
    }

    /**
     * The cache path mirrors the (unencoded) URL path after the base prefix —
     * the invariant behind the static-serve property and build/runtime
     * cache equivalence.
     */
    public function cachePath(string $path, array $params = []): string
    {
        return $this->relativePath($path, $this->params->mapPresetAlias($params));
    }

    /**
     * Reverse of url() for glider-served URLs: extracts (source path, params,
     * extension) from an absolute or relative URL, or returns null when the
     * URL is not served by the glider route (e.g. a direct-serve asset URL).
     *
     * @return array{path: string, params: array<string, string>, extension: string}|null
     */
    public function parseUrl(string $url): ?array
    {
        $urlPath = parse_url($url, PHP_URL_PATH);

        if (! is_string($urlPath) || ! str_starts_with($urlPath, $this->prefix() . '/')) {
            return null;
        }

        $relative = substr($urlPath, strlen($this->prefix()) + 1);
        $decoded = implode('/', array_map(rawurldecode(...), explode('/', $relative)));

        return $this->codec->parseRelativePath($decoded);
    }

    private function relativePath(string $path, array $params): string
    {
        $sourcePath = $this->normalizeSourcePath($path);
        $resolved = $this->params->routeParams($sourcePath, $params);

        return $this->codec->buildRelativePath($sourcePath, $resolved['params'], $resolved['extension']);
    }

    private function prefix(): string
    {
        return '/' . trim((string) config('glider.base_url'), '/');
    }

    private function normalizeSourcePath(string $path): string
    {
        if (Str::isUrl($path) && Str::startsWith($path, config('app.url')) && ! Str::startsWith($path, url(config('glider.base_url')))) {
            $path = Str::after($path, config('app.url'));
        }

        if (Str::isUrl($path)) {
            return $path;
        }

        // Remove query parameters from local paths if they exist
        if (str_contains($path, '?')) {
            $path = explode('?', $path)[0];
        }

        $path = ltrim($path, '/');

        $this->pathValidator->validate($path);

        return $path;
    }
}
