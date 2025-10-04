<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Glide\Server;
use League\Glide\Signatures\SignatureInterface;
use Netzarbeiter\FlysystemHttp\HttpAdapterPsr;

use function Illuminate\Filesystem\join_paths;

final class GlideService
{
    public function decodeParams(string $string): array
    {
        $decoded = base64_decode($this->base64UrlToBase64($string), true);
        if ($decoded === false) {
            return [];
        }
        $data = json_decode($decoded, true);
        return is_array($data) ? $data : [];
    }

    public function decodePath(string $string): string
    {
        $decoded = base64_decode($this->base64UrlToBase64($string), true);
        return $decoded === false ? '' : $decoded;
    }

    public function getCachePath(string $path, array $params = []): string
    {
        $routeParams = $this->getRouteParams($path, $params);
        $fullRoute = route('glide', $routeParams, false);

        return ltrim(Str::after($fullRoute, '/' . config('laravel-glider.base_url')), '/');
    }

    public function getSourceFilesystem(string $path): Filesystem
    {
        $adapter = new LocalFilesystemAdapter(config('laravel-glider.source'));
        if (Str::isUrl($path)) {
            // Extract base URL for HTTP filesystem
            $parsedUrl = parse_url($path);
            if (! $parsedUrl || ! isset($parsedUrl['scheme'], $parsedUrl['host'])) {
                throw new InvalidArgumentException("Invalid URL provided: {$path}");
            }

            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $baseUrl .= ':' . $parsedUrl['port'];
            }
            // Include base path if present (e.g., https://cdn.example.com/images)
            if (isset($parsedUrl['path']) && $parsedUrl['path'] !== '' && $parsedUrl['path'] !== '/') {
                $baseUrl .= rtrim($parsedUrl['path'], '/');
            }

            $adapter = HttpAdapterPsr::fromUrl($baseUrl);
        }

        return new Filesystem($adapter);
    }

    public function getUrl(string $path, array $params = []): string
    {
        // The signature is created later and should be ignored even if provided as a parameter
        unset($params['s']);

        // Sometimes we can directly serve the image
        if ($params === [] && Str::isUrl($path)) {
            return $path;
        }

        // Sometimes we can directly serve the image from the public disk
        if ($params === []) {
            $publicRoot = config('filesystems.disks.public.root');
            $sourceRoot = config('laravel-glider.source');

            if (is_string($publicRoot) && is_string($sourceRoot) && Str::startsWith($sourceRoot, $publicRoot)) {
                return Storage::disk('public')->url($path);
            }

            if (Str::startsWith($sourceRoot, storage_path())) {
                return asset(join_paths(Str::after($sourceRoot, storage_path()), $path));
            }
        }

        // Now we determine the route parameters including the signature (which depends on the other parameters)
        $routeParams = $this->getRouteParams($path, $params);

        $routeParams['s'] = app(SignatureInterface::class)
            ->generateSignature(route('glide', $routeParams, false), []);

        return route('glide', $routeParams);
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
        $presets = config('laravel-glider.background_presets', []);

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
        $pathForExt = (string) parse_url($path, PHP_URL_PATH);
        $ext = strtolower(! in_array(pathinfo($pathForExt, PATHINFO_EXTENSION), ['', '0'], true) ? pathinfo($pathForExt, PATHINFO_EXTENSION) : '');

        // Merge with server defaults/presets so fm from presets/defaults is considered
        $resolvedParams = $parameters;
        if (app()->bound(Server::class)) {
            $resolvedParams = app(Server::class)->getAllParams($parameters);
        }

        $format = $resolvedParams['fm'] ?? ($ext !== '' ? $ext : null);
        $extension = $format === 'pjpg' ? 'jpg' : ($format ?? 'jpg');

        // If fm is redundant (same as chosen extension), avoid including it explicitly in the URL params
        if (array_key_exists('fm', $parameters) && ($parameters['fm'] === $extension || $parameters['fm'] === 'pjpg' && $extension === 'jpg')) {
            unset($parameters['fm']);
        }

        return [
            'encoded_path'   => $this->encodePath($path),
            'encoded_params' => $this->encodeParams($parameters),
            'extension'      => $extension,
        ];
    }

    private function encodePath(string $path): string
    {
        if (Str::isUrl($path) && Str::startsWith($path, config('app.url')) && ! Str::startsWith($path, url(config('laravel-glider.base_url')))) {
            $path = Str::after($path, config('app.url'));
        }

        // Remove query parameters from path if they exist
        if (str_contains($path, '?')) {
            $path = explode('?', $path)[0];
        }

        $path = ltrim($path, '/');

        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }

    private function encodeParams(array $params): string
    {
        if (app()->bound(Server::class)) {
            $params = app(Server::class)->getAllParams($params);
        }

        unset($params['s'], $params['p']);
        $params = array_map('strval', $params);
        ksort($params);

        $json = json_encode($params, JSON_UNESCAPED_SLASHES);
        return rtrim(strtr(base64_encode($json ?: '{}'), '+/', '-_'), '=');
    }

    private function base64UrlToBase64(string $input): string
    {
        $b64 = strtr($input, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad !== 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        return $b64;
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
