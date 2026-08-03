<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Commands;

use Daikazu\LaravelGlider\Build\ConversionResolver;
use Daikazu\LaravelGlider\Build\TemplateScanner;
use Daikazu\LaravelGlider\Facades\Glider;
use Illuminate\Console\Command;
use League\Glide\Server;
use Throwable;

/**
 * Prebuilds every statically-discoverable Glide conversion referenced from
 * Blade templates, so those conversions are already cached before the
 * first real request for them arrives.
 *
 * The generated cache entries MUST be identical to what a live request for
 * the same conversion would produce — that equivalence is enforced by
 * {@see ConversionResolver}, which mirrors the
 * exact param-resolution each component/facade usage performs at request
 * time.
 */
class BuildCommand extends Command
{
    public $signature = 'glider:build
        {--dry-run : List conversions without generating them}
        {--cache-path= : Write conversions to this path instead of the configured cache (e.g. public/img)}';

    public $description = 'Prebuild all statically discoverable Glide conversions found in Blade templates';

    public function handle(TemplateScanner $scanner, ConversionResolver $resolver): int
    {
        $cacheOverride = $this->option('cache-path');

        if (is_string($cacheOverride) && $cacheOverride !== '') {
            config(['glider.cache' => $cacheOverride]);
        }

        // Resolved after the override so the server's cache filesystem
        // reflects it; method injection would resolve too early.
        $server = app(Server::class);

        $paths = (array) config('glider.build.paths', []);

        foreach ($paths as $path) {
            if (! is_string($path) || ! is_dir($path)) {
                $label = is_string($path) ? $path : (json_encode($path) ?: 'null');
                $this->warn("Configured build path does not exist, skipping: {$label}");
            }
        }

        $result = $scanner->scan($paths);

        /** @var array<string, array{path: string, params: array}> $jobsByCachePath */
        $jobsByCachePath = [];
        $resolveFailures = [];

        foreach ($result['usages'] as $usage) {
            try {
                $usageJobs = $resolver->jobs($usage);
            } catch (Throwable $e) {
                $resolveFailures[] = [
                    'path'   => $usage->src,
                    'params' => $usage->attributes,
                    'reason' => $e->getMessage(),
                ];

                continue;
            }

            foreach ($usageJobs as $job) {
                $cachePath = Glider::getCachePath($job['path'], $job['params']);
                $jobsByCachePath[$cachePath] = $job;
            }
        }

        $jobs = array_values($jobsByCachePath);

        if ($this->option('dry-run')) {
            return $this->reportDryRun($jobs, $result['dynamic'], $resolveFailures);
        }

        $generated = 0;
        $failures = $resolveFailures;

        foreach ($jobs as $job) {
            try {
                $server->setSource(Glider::getSourceFilesystem($job['path']));
                $server->setCachePathCallable(fn (string $p, array $ps = []): string => Glider::getCachePath($p, $ps));
                $server->makeImage(Glider::getImagePath($job['path']), $job['params']);
                $generated++;
            } catch (Throwable $e) {
                $failures[] = [
                    'path'   => $job['path'],
                    'params' => $job['params'],
                    'reason' => $e->getMessage(),
                ];
            }
        }

        $this->reportSummary($generated, $result['dynamic'], $failures);

        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<array{path: string, params: array}>  $jobs
     * @param  list<array{file: string, tag: string}>  $dynamic
     * @param  list<array{path: string, params: array, reason: string}>  $resolveFailures
     */
    private function reportDryRun(array $jobs, array $dynamic, array $resolveFailures): int
    {
        $this->info(sprintf('%d conversion(s) would be generated:', count($jobs)));

        foreach ($jobs as $job) {
            $this->line(' - ' . $job['path'] . ' ' . json_encode($job['params']));
        }

        $this->reportDynamic($dynamic);

        if ($resolveFailures !== []) {
            $this->line('failed to resolve: ' . count($resolveFailures));

            foreach ($resolveFailures as $failure) {
                $this->error(" - {$failure['path']}: {$failure['reason']}");
            }
        }

        return $resolveFailures === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<array{file: string, tag: string}>  $dynamic
     * @param  list<array{path: string, params: array, reason: string}>  $failures
     */
    private function reportSummary(int $generated, array $dynamic, array $failures): void
    {
        $this->newLine();
        $this->info("generated: {$generated}");

        $this->reportDynamic($dynamic);

        $this->line('failed: ' . count($failures));

        foreach ($failures as $failure) {
            $this->error(" - {$failure['path']} " . json_encode($failure['params']) . ": {$failure['reason']}");
        }
    }

    /**
     * @param  list<array{file: string, tag: string}>  $dynamic
     */
    private function reportDynamic(array $dynamic): void
    {
        $this->line('skipped (dynamic src): ' . count($dynamic));

        foreach ($dynamic as $entry) {
            $this->line(" - {$entry['file']}: {$entry['tag']}");
        }
    }
}
