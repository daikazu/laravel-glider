<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider;

use Daikazu\LaravelGlider\Security\PathValidator;
use Daikazu\LaravelGlider\Support\ParamResolver;
use Daikazu\LaravelGlider\Support\PathCodec;
use Daikazu\LaravelGlider\Support\SourceResolver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use League\Glide\Signatures\SignatureInterface;

use function Illuminate\Filesystem\join_paths;

final class GlideService
{
    public function decodeParams(string $string): array
    {
        return app(PathCodec::class)->decodeParams($string);
    }

    public function decodePath(string $string): string
    {
        $decoded = app(PathCodec::class)->decode($string);
        if ($decoded === null) {
            return '';
        }

        // Validate decoded path for security
        if (! Str::isUrl($decoded)) {
            app(PathValidator::class)->validate($decoded);
        }

        return $decoded;
    }

    public function getCachePath(string $path, array $params = []): string
    {
        $routeParams = $this->getRouteParams($path, $params);
        $fullRoute = route('glider', $routeParams, false);

        return ltrim(Str::after($fullRoute, '/' . config('glider.base_url')), '/');
    }

    public function getSourceFilesystem(string $path): FilesystemOperator
    {
        return app(SourceResolver::class)->filesystemFor($path);
    }

    /**
     * Get the image path to use with the filesystem adapter
     * For URLs, returns the path+query portion
     * For local paths, returns the path as-is
     */
    public function getImagePath(string $path): string
    {
        return app(SourceResolver::class)->imagePath($path);
    }

    public function getUrl(string $path, array $params = []): string
    {
        // The signature is created later and should be ignored even if provided as a parameter
        unset($params['s']);

        // Map 'preset' to 'p' for League/Glide compatibility
        // Users use glide-preset="name" which becomes ['preset' => 'name']
        // But League/Glide expects ['p' => 'name'] for preset lookups
        $params = app(ParamResolver::class)->mapPresetAlias($params);

        // Sometimes we can directly serve the image from the public disk
        // (Only for local paths, not URLs)
        if ($params === [] && ! Str::isUrl($path)) {
            $publicRoot = config('filesystems.disks.public.root');
            $sourceRoot = config('glider.source');

            if (is_string($publicRoot) && is_string($sourceRoot) && Str::startsWith($sourceRoot, $publicRoot)) {
                return Storage::disk('public')->url($path);
            }

            if (Str::startsWith($sourceRoot, storage_path())) {
                return asset(join_paths(Str::after($sourceRoot, storage_path()), $path));
            }
        }

        // Now we determine the route parameters
        $routeParams = $this->getRouteParams($path, $params);

        // Only add signature if secure mode is enabled
        if (config('glider.secure', true)) {
            $signedParams = app(SignatureInterface::class)
                ->addSignature(route('glider', $routeParams, false), []);

            $routeParams['s'] = $signedParams['s'];
        }

        return route('glider', $routeParams);
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
        $urls = [];

        foreach ($breakpoints as $breakpoint => $params) {
            // Merge base params with breakpoint-specific params
            $finalParams = array_merge($baseParams, $params);

            $urls[$breakpoint] = [
                'url'       => $this->getUrl($path, $finalParams),
                'params'    => $finalParams,
                'min_width' => $this->getBreakpointWidth($breakpoint),
            ];
        }

        return $urls;
    }

    /**
     * Get background preset configuration
     */
    public function getBackgroundPreset(string $presetName): array
    {
        $presets = config('glider.background_presets', []);

        if (! isset($presets[$presetName])) {
            throw new InvalidArgumentException("Background preset '{$presetName}' not found");
        }

        return $presets[$presetName];
    }

    /**
     * Generate CSS for responsive background images
     */
    public function generateBackgroundCSS(string $path, array $breakpoints, string $selector, array $options = []): string
    {
        $urls = $this->getResponsiveBackgroundUrls($path, $breakpoints);

        $position = $options['position'] ?? 'center';
        $size = $options['size'] ?? 'cover';
        $repeat = $options['repeat'] ?? 'no-repeat';
        $attachment = $options['attachment'] ?? 'scroll';

        $cssRules = [];

        // Generate default background (first/smallest breakpoint)
        $firstUrl = reset($urls);
        if ($firstUrl) {
            $cssRules[] = "{$selector} {";
            $cssRules[] = "    background-image: url('{$firstUrl['url']}');";
            $cssRules[] = "    background-position: {$position};";
            $cssRules[] = "    background-size: {$size};";
            $cssRules[] = "    background-repeat: {$repeat};";
            $cssRules[] = "    background-attachment: {$attachment};";
            $cssRules[] = '}';
        }

        // Generate media queries for larger breakpoints
        foreach ($urls as $data) {
            if ($data['min_width'] > 0) {
                $cssRules[] = "@media (min-width: {$data['min_width']}px) {";
                $cssRules[] = "    {$selector} {";
                $cssRules[] = "        background-image: url('{$data['url']}');";
                $cssRules[] = '    }';
                $cssRules[] = '}';
            }
        }

        return implode(PHP_EOL, $cssRules);
    }

    private function getRouteParams(string $path, array $parameters = []): array
    {
        $resolved = app(ParamResolver::class)->routeParams($path, $parameters);

        return [
            'encoded_path'   => $this->encodePath($path),
            'encoded_params' => $this->encodeParams($resolved['params']),
            'extension'      => $resolved['extension'],
        ];
    }

    private function encodePath(string $path): string
    {
        if (Str::isUrl($path) && Str::startsWith($path, config('app.url')) && ! Str::startsWith($path, url(config('glider.base_url')))) {
            $path = Str::after($path, config('app.url'));
        }

        // Remove query parameters from path if they exist
        if (str_contains($path, '?')) {
            $path = explode('?', $path)[0];
        }

        $path = ltrim($path, '/');

        // Validate local paths for security
        if (! Str::isUrl($path)) {
            app(PathValidator::class)->validate($path);
        }

        return app(PathCodec::class)->encode($path);
    }

    private function encodeParams(array $params): string
    {
        $normalized = app(ParamResolver::class)->normalize($params);

        return app(PathCodec::class)->encodeParams($normalized);
    }

    /**
     * Convert breakpoint name to minimum width in pixels
     */
    private function getBreakpointWidth(string | int $breakpoint): int
    {
        // If it's already numeric, use it
        if (is_numeric($breakpoint)) {
            return (int) $breakpoint;
        }

        // Map common breakpoint names to pixel values
        $breakpointMap = [
            'xs'  => 0,
            'sm'  => 576,
            'md'  => 768,
            'lg'  => 992,
            'xl'  => 1200,
            '2xl' => 1400,
        ];

        return $breakpointMap[$breakpoint] ?? 0;
    }
}
