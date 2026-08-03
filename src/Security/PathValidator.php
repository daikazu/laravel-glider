<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Security;

use InvalidArgumentException;

use function Illuminate\Filesystem\join_paths;

final class PathValidator
{
    public function __construct(private ?string $sourceRoot) {}

    /**
     * Validate local file path to prevent directory traversal attacks
     *
     * @throws InvalidArgumentException
     */
    public function validate(string $path): void
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

        if ($this->sourceRoot === null) {
            return;
        }

        // Validate that resolved path stays within source directory
        $sourcePath = (string) realpath($this->sourceRoot);
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
}
