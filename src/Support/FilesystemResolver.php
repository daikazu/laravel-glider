<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;

/**
 * Resolves `glider.source` / `glider.cache` / `glider.watermarks` config
 * values — either a plain filesystem path or a `['disk' => ..., 'prefix' => ...]`
 * array — into Flysystem operators or local paths.
 */
final class FilesystemResolver
{
    /**
     * @param  string|array{disk: string, prefix?: string}  $config
     */
    public function resolve(string | array $config): FilesystemOperator
    {
        if (is_string($config)) {
            return new Filesystem(new LocalFilesystemAdapter($this->absolutePath($config)));
        }

        $disk = Storage::disk($config['disk']);

        if (isset($config['prefix']) && $config['prefix'] !== '') {
            return new Filesystem(new PathPrefixedAdapter($disk->getAdapter(), $config['prefix']));
        }

        return $disk->getDriver();
    }

    /**
     * @param  string|array{disk: string, prefix?: string}  $config
     */
    public function localPath(string | array $config): ?string
    {
        if (is_string($config)) {
            return $this->absolutePath($config);
        }

        $disk = Storage::disk($config['disk']);

        if (! $disk->getAdapter() instanceof LocalFilesystemAdapter) {
            return null;
        }

        return $disk->path($config['prefix'] ?? '');
    }

    /**
     * Human-readable label for a filesystem config value, for CLI output
     * (`glider:clear`, `artisan about`).
     *
     * @param  string|array{disk: string, prefix?: string}  $config
     */
    public function describe(string | array $config): string
    {
        if (is_string($config)) {
            return $this->absolutePath($config);
        }

        $prefix = $config['prefix'] ?? '';

        return sprintf("disk '%s'%s", $config['disk'], $prefix !== '' ? " (prefix '{$prefix}')" : '');
    }

    /**
     * Relative paths (e.g. GLIDER_CACHE_PATH=public/glider) are anchored to the
     * application root rather than the process CWD, which differs between
     * artisan and web requests.
     */
    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }
}
