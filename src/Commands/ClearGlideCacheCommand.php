<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use SplFileInfo;
use Throwable;

class ClearGlideCacheCommand extends Command
{
    public $signature = 'glider:clear {--force : Force the operation to run when in production}';

    public $description = 'Remove the Glide cached images';

    public function handle(): int
    {

        $cachePath = (string) config('laravel-glider.cache');

        // Fun banner
        $this->line('');
        $this->line('<fg=bright-cyan>✈️ GLIDER CACHE CLEAR</> <fg=gray>(fasten your seatbelts)</>');
        $this->line('<fg=bright-blue>──────────────────────────────────────────────</>');

        if ($cachePath === '') {
            $this->warn('Glide cache path is not configured (glider.cache). Nothing to clear.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && app()->environment('production') && ! $this->confirm("You are in production. This will delete all files under:\n{$cachePath}\nDo you wish to continue?")) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        /** @var Filesystem $fs */
        $fs = app(Filesystem::class);

        if (! $fs->isDirectory($cachePath)) {
            $this->info("Glide cache directory does not exist: {$cachePath}");
            $this->line('<fg=green>✨ All clear already!</>');
            return self::SUCCESS;
        }

        // Collect files to show progress and stats
        try {
            $files = $fs->allFiles($cachePath, true);
        } catch (Throwable $e) {
            $this->error("Failed to scan Glide cache: {$e->getMessage()}");
            return self::FAILURE;
        }

        $totalFiles = count($files);
        $totalBytes = 0;
        foreach ($files as $file) {
            $totalBytes += $file->getSize();
        }

        $this->line("📁 Cache path: <fg=bright-yellow>{$cachePath}</>");
        $this->line("🔍 Found: <fg=bright-green>{$totalFiles}</> file(s), <fg=bright-green>{$this->humanBytes($totalBytes)}</> total");
        $this->line("<fg=bright-blue>──────────────────────────────────────────────</>\n");

        if ($totalFiles === 0) {
            // Still ensure directories are tidy
            try {
                $fs->cleanDirectory($cachePath);
            } catch (Throwable) {
                // ignore
            }
            $this->line('<fg=green>✨ Nothing to delete. Your cache already empty/</>');
            return self::SUCCESS;
        }

        $start = microtime(true);

        // Progress bar deletion
        $deleted = 0;
        $deletedBytes = 0;

        $this->withProgressBar($files, function ($file) use ($fs, &$deleted, &$deletedBytes): void {
            /** @var SplFileInfo $file */
            $size = $file->getSize();
            try {
                $fs->delete($file->getPathname());
                $deleted++;
                $deletedBytes += $size;
            } catch (Throwable) {
                // Ignore individual file failures; we will report at the end
            }
        });
        $this->newLine();

        // Clean up any leftover empty directories
        try {
            $fs->cleanDirectory($cachePath);
        } catch (Throwable) {
            // ignore
        }

        $elapsed = microtime(true) - $start;

        $this->line("🗑️  Removed: <fg=bright-green>{$deleted}</> file(s), <fg=bright-green>{$this->humanBytes($deletedBytes)}</>");
        $this->line(sprintf('⏱️  Time: <fg=bright-magenta>%.2fs</>', $elapsed));
        $this->line('<fg=green>✨ Glide cache obliterated. Fresh pixels await! 🚀</>');
        $this->line('');

        return self::SUCCESS;

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
