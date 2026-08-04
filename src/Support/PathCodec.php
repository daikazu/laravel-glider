<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Illuminate\Support\Str;

/**
 * Builds and parses glider's relative URL paths:
 *
 *   {source dirs...}/{name}~{token}.{output extension}
 *
 * The path and name stay human-readable (SEO, debuggability); the token is
 * base64url of an http_build_query string carrying the manipulation params
 * plus internal keys: `se` (the source file's extension) or `r` (a full
 * remote source URL). http_build_query/parse_str make the token safe for
 * param values containing slashes, underscores, or hyphens (e.g. watermark
 * paths), and base64url keeps it a single filesystem-safe segment — which
 * the static-serve property depends on.
 *
 * `~` delimits name from token: it is an RFC 3986 unreserved character that
 * never appears in base64url output, so splitting on the LAST `~` is
 * unambiguous even for names that contain tildes.
 */
final class PathCodec
{
    private const array ALLOWED_EXTENSIONS = ['jpg', 'png', 'gif', 'webp', 'avif', 'tiff'];

    /**
     * Internal token keys that never collide with Glide params.
     */
    private const string SOURCE_EXTENSION_KEY = 'se';

    private const string REMOTE_URL_KEY = 'r';

    public function buildRelativePath(string $sourcePath, array $params, string $extension): string
    {
        unset($params[self::SOURCE_EXTENSION_KEY], $params[self::REMOTE_URL_KEY]);

        if (Str::isUrl($sourcePath)) {
            $urlPath = (string) parse_url($sourcePath, PHP_URL_PATH);
            $name = $this->sanitizeName(pathinfo($urlPath, PATHINFO_FILENAME));
            $params[self::REMOTE_URL_KEY] = $sourcePath;

            return $name . '~' . $this->encodeToken($params) . '.' . $extension;
        }

        $sourceExtension = pathinfo($sourcePath, PATHINFO_EXTENSION);
        if ($sourceExtension !== '') {
            $params[self::SOURCE_EXTENSION_KEY] = $sourceExtension;
        }

        $directory = str_contains($sourcePath, '/') ? Str::beforeLast($sourcePath, '/') : '';
        $name = pathinfo($sourcePath, PATHINFO_FILENAME);

        $filename = $name . '~' . $this->encodeToken($params) . '.' . $extension;

        return $directory === '' ? $filename : $directory . '/' . $filename;
    }

    /**
     * @return array{path: string, params: array<string, string>, extension: string}|null
     */
    public function parseRelativePath(string $relative): ?array
    {
        $filename = str_contains($relative, '/') ? Str::afterLast($relative, '/') : $relative;
        $directory = str_contains($relative, '/') ? Str::beforeLast($relative, '/') : '';

        if (! str_contains($filename, '~')) {
            return null;
        }

        $name = Str::beforeLast($filename, '~');
        $tokenAndExtension = Str::afterLast($filename, '~');

        if ($name === '' || ! str_contains($tokenAndExtension, '.')) {
            return null;
        }

        $token = Str::beforeLast($tokenAndExtension, '.');
        $extension = strtolower(Str::afterLast($tokenAndExtension, '.'));

        if ($token === '' || ! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return null;
        }

        $params = $this->decodeToken($token);
        if ($params === null) {
            return null;
        }

        if (isset($params[self::REMOTE_URL_KEY])) {
            $url = $params[self::REMOTE_URL_KEY];
            unset($params[self::REMOTE_URL_KEY], $params[self::SOURCE_EXTENSION_KEY]);

            if (! Str::isUrl($url)) {
                return null;
            }

            return ['path' => $url, 'params' => $params, 'extension' => $extension];
        }

        $sourceExtension = $params[self::SOURCE_EXTENSION_KEY] ?? '';
        unset($params[self::SOURCE_EXTENSION_KEY]);

        if ($sourceExtension !== '' && preg_match('/^[a-zA-Z0-9]+$/', $sourceExtension) !== 1) {
            return null;
        }

        $sourceFile = $sourceExtension === '' ? $name : $name . '.' . $sourceExtension;
        $path = $directory === '' ? $sourceFile : $directory . '/' . $sourceFile;

        return ['path' => $path, 'params' => $params, 'extension' => $extension];
    }

    /**
     * Percent-encode a relative path for use in a URL, segment by segment,
     * leaving `/` and RFC 3986 unreserved characters (including `~`) intact.
     */
    public function encodeUrlSegments(string $relative): string
    {
        return implode('/', array_map(rawurlencode(...), explode('/', $relative)));
    }

    private function encodeToken(array $params): string
    {
        ksort($params);

        return rtrim(strtr(base64_encode(http_build_query($params)), '+/', '-_'), '=');
    }

    /**
     * @return array<string, string>|null
     */
    private function decodeToken(string $token): ?array
    {
        $b64 = strtr($token, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad !== 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($b64, true);

        if ($decoded === false || $decoded === '' || preg_match('//u', $decoded) !== 1 || str_contains($decoded, "\0")) {
            return null;
        }

        parse_str($decoded, $params);

        if ($params === []) {
            return null;
        }

        foreach ($params as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                return null;
            }
        }

        return $params;
    }

    /**
     * Display-only name for remote sources — must stay a single, clean
     * path segment; the real source URL lives in the token.
     */
    private function sanitizeName(string $name): string
    {
        $clean = (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $name);
        $clean = trim($clean, '.-');

        return $clean === '' ? 'remote' : $clean;
    }
}
