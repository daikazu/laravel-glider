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
        if ($decoded === false) {
            return '';
        }

        // Validate decoded path for security
        if (! Str::isUrl($decoded)) {
            $this->validateLocalPath($decoded);
        }

        return $decoded;
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

        // Check if path contains a scheme (URL-like)
        if (Str::isUrl($path) || str_contains($path, '://')) {
            // Validate URL to prevent SSRF attacks
            $this->validateRemoteUrl($path);

            // Extract base URL for HTTP filesystem
            $parsedUrl = parse_url($path);
            if ($parsedUrl === false || ! isset($parsedUrl['scheme'], $parsedUrl['host'])) {
                throw new InvalidArgumentException("Invalid URL provided: {$path}");
            }

            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $baseUrl .= ':' . $parsedUrl['port'];
            }

            $adapter = HttpAdapterPsr::fromUrl($baseUrl);
        }

        return new Filesystem($adapter);
    }

    /**
     * Get the image path to use with the filesystem adapter
     * For URLs, returns the path+query portion
     * For local paths, returns the path as-is
     */
    public function getImagePath(string $path): string
    {
        if (Str::isUrl($path)) {
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

        return $path;
    }

    public function getUrl(string $path, array $params = []): string
    {
        // The signature is created later and should be ignored even if provided as a parameter
        unset($params['s']);

        // Map 'preset' to 'p' for League/Glide compatibility
        // Users use glide-preset="name" which becomes ['preset' => 'name']
        // But League/Glide expects ['p' => 'name'] for preset lookups
        if (isset($params['preset'])) {
            $params['p'] = $params['preset'];
            unset($params['preset']);
        }

        // Sometimes we can directly serve the image from the public disk
        // (Only for local paths, not URLs)
        if ($params === [] && ! Str::isUrl($path)) {
            $publicRoot = config('filesystems.disks.public.root');
            $sourceRoot = config('laravel-glider.source');

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
        if (config('laravel-glider.secure', true)) {
            $signedParams = app(SignatureInterface::class)
                ->addSignature(route('glide', $routeParams, false), []);

            $routeParams['s'] = $signedParams['s'];
        }

        return route('glide', $routeParams);
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
        $ext = strtolower(in_array(pathinfo($pathForExt, PATHINFO_EXTENSION), ['', '0'], true) ? '' : pathinfo($pathForExt, PATHINFO_EXTENSION));

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

        // Validate local paths for security
        if (! Str::isUrl($path)) {
            $this->validateLocalPath($path);
        }

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

    /**
     * Validate local file path to prevent directory traversal attacks
     *
     * @throws InvalidArgumentException
     */
    private function validateLocalPath(string $path): void
    {
        // Check for null bytes - a common attack vector
        if (str_contains($path, "\0")) {
            throw new InvalidArgumentException('Invalid path: null byte detected');
        }

        // Remove any directory traversal sequences
        $normalized = str_replace(['../', '.\\', '..\\'], '', $path);

        // Additional check: ensure the normalized path doesn't still contain traversal patterns
        if ($normalized !== $path) {
            throw new InvalidArgumentException('Invalid path: directory traversal attempt detected');
        }

        // Validate that resolved path stays within source directory
        $sourcePath = (string) realpath(config('laravel-glider.source'));
        if ($sourcePath === '') {
            throw new InvalidArgumentException('Invalid source configuration: path does not exist');
        }

        // Construct the full path
        $fullPath = join_paths($sourcePath, $normalized);

        // Get the real path (resolves symlinks and relative paths)
        $resolvedPath = realpath($fullPath);

        // If realpath returns false, the file doesn't exist yet (which is OK for generation)
        // But we still need to validate the parent directory
        if ($resolvedPath === false) {
            // Check parent directory instead
            $parentPath = dirname($fullPath);
            $resolvedParentPath = realpath($parentPath);

            // If parent also doesn't exist, validate the normalized path structure
            if ($resolvedParentPath !== false) {
                if (! str_starts_with($resolvedParentPath, $sourcePath)) {
                    throw new InvalidArgumentException('Invalid path: outside source directory');
                }
            } else {
                // Parent doesn't exist - just ensure no traversal in the path itself
                $absolutePath = $sourcePath . DIRECTORY_SEPARATOR . $normalized;
                if (! str_starts_with($absolutePath, $sourcePath)) {
                    throw new InvalidArgumentException('Invalid path: outside source directory');
                }
            }
        } else {
            // File exists - ensure it's within source directory
            if (! str_starts_with($resolvedPath, $sourcePath)) {
                throw new InvalidArgumentException('Invalid path: outside source directory');
            }
        }
    }

    /**
     * Validate remote URL to prevent SSRF (Server-Side Request Forgery) attacks
     *
     * @throws InvalidArgumentException
     */
    private function validateRemoteUrl(string $url): void
    {
        // Parse the URL
        $parsed = parse_url($url);
        if ($parsed === false || ! isset($parsed['scheme'], $parsed['host'])) {
            throw new InvalidArgumentException('Invalid URL provided');
        }

        // Only allow HTTP and HTTPS schemes
        $allowedSchemes = ['http', 'https'];
        if (! in_array(strtolower($parsed['scheme']), $allowedSchemes, true)) {
            throw new InvalidArgumentException('Invalid URL scheme: only http and https are allowed');
        }

        $host = $parsed['host'];

        // Strip brackets from IPv6 addresses
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        // Block localhost variations
        $localhostPatterns = [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
            '0:0:0:0:0:0:0:1',
        ];

        if (in_array(strtolower($host), $localhostPatterns, true)) {
            throw new InvalidArgumentException('Access to localhost is not allowed');
        }

        // Resolve hostname to IP address(es) for validation
        $ips = @gethostbynamel($host);

        // If DNS resolution fails, fall back to checking if host is already an IP
        if ($ips === false) {
            // Check if host is an IP address (including IPv6)
            if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                $ips = [$host];
            } elseif (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                // Handle IPv6 addresses
                $ips = [$host];
            } else {
                // DNS resolution failed and it's not an IP
                // This could be a malformed hostname or network issue
                // For security, we'll allow it to fail here rather than block legitimate hostnames
                // The actual connection attempt will fail naturally if the host doesn't exist
                return;
            }
        }

        // Validate each resolved IP address
        foreach ($ips as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                throw new InvalidArgumentException('Access to private or reserved IP addresses is not allowed');
            }
        }

        // Block common dangerous ports
        if (isset($parsed['port'])) {
            $dangerousPorts = [
                22,    // SSH
                23,    // Telnet
                25,    // SMTP
                3306,  // MySQL
                5432,  // PostgreSQL
                6379,  // Redis
                27017, // MongoDB
                11211, // Memcached
            ];

            if (in_array((int) $parsed['port'], $dangerousPorts, true)) {
                throw new InvalidArgumentException('Access to port ' . $parsed['port'] . ' is not allowed');
            }
        }
    }

    /**
     * Check if an IP address is private or reserved
     */
    private function isPrivateOrReservedIp(string $ip): bool
    {
        // Validate IP format
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true; // Invalid IP, treat as blocked
        }

        // Check for private and reserved IP ranges
        // This includes:
        // - Private IPv4 ranges (10.x.x.x, 172.16-31.x.x, 192.168.x.x)
        // - Loopback (127.x.x.x, ::1)
        // - Link-local (169.254.x.x, fe80::/10)
        // - Multicast and broadcast addresses
        // - Cloud metadata endpoints (169.254.169.254)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        // Additional check for AWS metadata endpoint
        if ($ip === '169.254.169.254') {
            return true;
        }

        return false;
    }
}
