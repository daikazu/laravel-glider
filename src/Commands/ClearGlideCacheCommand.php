<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Commands;

use Daikazu\LaravelGlider\Support\FilesystemResolver;
use Illuminate\Console\Command;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Throwable;

class ClearGlideCacheCommand extends Command
{
    public $signature = 'glider:clear
        {--force : Force the operation to run when in production}
        {--cache-path= : Clear this path instead of the configured cache (e.g. a baked public/img dir)}';

    public $description = 'Remove the Glider cached images';

    public function handle(FilesystemResolver $resolver): int
    {
        $cacheOverride = $this->option('cache-path');

        $cacheConfig = is_string($cacheOverride) && $cacheOverride !== ''
            ? $cacheOverride
            : config('glider.cache');

        // Fun banner
        $this->line('');
        $this->line('<fg=bright-cyan>✈️ GLIDER CACHE CLEAR</> <fg=gray>(fasten your seatbelts)</>');
        $this->line('<fg=bright-blue>──────────────────────────────────────────────</>');

        $isPath = is_string($cacheConfig) && $cacheConfig !== '';
        $isDisk = is_array($cacheConfig) && isset($cacheConfig['disk']) && is_string($cacheConfig['disk']);

        if (! $isPath && ! $isDisk) {
            $this->warn('Glider cache is not configured (glider.cache). Nothing to clear.');

            return self::SUCCESS;
        }

        /** @var string|array{disk: string, prefix?: string} $cacheConfig */
        $label = $resolver->describe($cacheConfig);

        if (! $this->option('force') && app()->environment('production') && ! $this->confirm("You are in production. This will delete all cached images in:\n{$label}\nDo you wish to continue?")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        try {
            $filesystem = $resolver->resolve($cacheConfig);
            $files = $this->cachedFiles($filesystem);
        } catch (Throwable $e) {
            $this->error("Failed to scan Glider cache: {$e->getMessage()}");

            return self::FAILURE;
        }

        $totalFiles = count($files);
        $totalBytes = array_sum(array_map(fn (array $file): int => $file['size'], $files));

        $this->line("📁 Cache: <fg=bright-yellow>{$label}</>");
        $this->line("🔍 Found: <fg=bright-green>{$totalFiles}</> file(s), <fg=bright-green>{$this->humanBytes($totalBytes)}</> total");
        $this->line("<fg=bright-blue>──────────────────────────────────────────────</>\n");

        if ($totalFiles === 0) {
            // Still sweep folders a previous or interrupted run left behind.
            $this->removeEmptyDirectories($filesystem);
            $this->line('<fg=green>✨ Nothing to delete. Your cache is already empty.</>');

            return self::SUCCESS;
        }

        $start = microtime(true);

        $deleted = 0;
        $deletedBytes = 0;

        $this->withProgressBar($files, function (array $file) use ($filesystem, &$deleted, &$deletedBytes): void {
            try {
                $filesystem->delete($file['path']);
                $deleted++;
                $deletedBytes += $file['size'];
            } catch (Throwable) {
                // Ignore individual file failures; totals reveal the shortfall
            }
        });
        $this->newLine();

        $this->removeEmptyDirectories($filesystem);

        $elapsed = microtime(true) - $start;

        $this->line("🗑️  Removed: <fg=bright-green>{$deleted}</> file(s), <fg=bright-green>{$this->humanBytes($deletedBytes)}</>");
        $this->line(sprintf('⏱️  Time: <fg=bright-magenta>%.2fs</>', $elapsed));
        $this->line('<fg=green>✨ Glider cache obliterated. Fresh pixels await! 🚀</>');
        $this->line('');

        return self::SUCCESS;
    }

    /**
     * All cached files with their sizes, excluding the .gitignore marker.
     *
     * @return list<array{path: string, size: int}>
     */
    private function cachedFiles(FilesystemOperator $filesystem): array
    {
        $files = [];

        foreach ($filesystem->listContents('', true) as $item) {
            /** @var StorageAttributes $item */
            if (! $item->isFile() || basename($item->path()) === '.gitignore') {
                continue;
            }

            $size = 0;

            try {
                $size = $filesystem->fileSize($item->path());
            } catch (Throwable) {
                // Size is display-only; deletion proceeds regardless
            }

            $files[] = ['path' => $item->path(), 'size' => $size];
        }

        return $files;
    }

    /**
     * Remove directories left empty by the file deletions (relevant when
     * group_cache_in_folders nests conversions in per-image folders). Deepest
     * first, and only when nothing remains inside. On object stores,
     * directories are virtual prefixes and the listing is typically empty —
     * this is then a no-op.
     */
    private function removeEmptyDirectories(FilesystemOperator $filesystem): void
    {
        try {
            $directories = [];

            foreach ($filesystem->listContents('', true) as $item) {
                /** @var StorageAttributes $item */
                if ($item->isDir()) {
                    $directories[] = $item->path();
                }
            }

            usort($directories, fn (string $a, string $b): int => substr_count($b, '/') <=> substr_count($a, '/'));

            foreach ($directories as $directory) {
                if (iterator_count($filesystem->listContents($directory, false)) === 0) {
                    $filesystem->deleteDirectory($directory);
                }
            }
        } catch (Throwable) {
            // Leftover empty folders are cosmetic; never fail the clear over them
        }
    }

    private function humanBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);

        return number_format($value, $precision) . ' ' . $units[$power];
    }
}
