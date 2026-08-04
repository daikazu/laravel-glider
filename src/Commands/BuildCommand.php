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
        {--static : Bake conversions into public/{base_url} for static serving instead of the configured cache}';

    public $description = 'Prebuild all statically discoverable Glide conversions found in Blade templates';

    public function handle(TemplateScanner $scanner, ConversionResolver $resolver): int
    {
        if ($this->option('static')) {
            // The static-serve location is derived from config, not typed by
            // hand — a mistyped path that doesn't match base_url would bake
            // files no request ever finds.
            config(['glider.cache' => public_path(trim((string) config('glider.base_url'), '/'))]);
        }

        // Resolved after the override so the server's cache filesystem
        // reflects it; method injection would resolve too early.
        $server = app(Server::class);

        $paths = (array) config('glider.build.paths', []);

        $this->line('');
        $this->line('<fg=bright-cyan>✈️ GLIDER BUILD</> <fg=gray>(warming up the pixels)</>');
        $this->line('<fg=bright-blue>──────────────────────────────────────────────</>');

        foreach ($paths as $path) {
            if (! is_string($path) || ! is_dir($path)) {
                $label = is_string($path) ? $path : (json_encode($path) ?: 'null');
                $this->warn("Configured build path does not exist, skipping: {$label}");
            }
        }

        $this->line(sprintf('🔍 Scanning <fg=bright-yellow>%d</> template path(s)…', count($paths)));

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

        $this->line(sprintf(
            '📋 Found: <fg=bright-green>%d</> usage(s) → <fg=bright-green>%d</> unique conversion(s), <fg=bright-yellow>%d</> dynamic (stay on-the-fly)',
            count($result['usages']),
            count($jobs),
            count($result['dynamic']),
        ));
        $this->line("<fg=bright-blue>──────────────────────────────────────────────</>\n");

        if ($this->option('dry-run')) {
            return $this->reportDryRun($jobs, $result['dynamic'], $resolveFailures);
        }

        $generated = 0;
        $failures = $resolveFailures;
        $start = microtime(true);

        if ($jobs !== []) {
            $bar = $this->output->createProgressBar(count($jobs));
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — <fg=bright-yellow>%message%</>');
            $bar->setMessage('warming up…');
            $bar->start();

            foreach ($jobs as $job) {
                $bar->setMessage($this->jobLabel($job));
                $bar->display();

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

                $bar->advance();
            }

            $bar->setMessage('done');
            $bar->finish();
            $this->newLine();
        }

        $this->reportSummary($generated, $result['dynamic'], $failures, microtime(true) - $start);

        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Compact single-line label for the conversion currently being generated.
     *
     * @param  array{path: string, params: array}  $job
     */
    private function jobLabel(array $job): string
    {
        $params = json_encode($job['params']) ?: '';
        $label = $job['path'] . ' ' . $params;

        return strlen($label) <= 70 ? $label : substr($label, 0, 67) . '…';
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
    private function reportSummary(int $generated, array $dynamic, array $failures, float $elapsed): void
    {
        $this->newLine();
        $this->line('<fg=bright-blue>──────────────────────────────────────────────</>');
        $this->line("✅ <fg=bright-green>generated: {$generated}</>");

        $this->reportDynamic($dynamic);

        $failureColor = $failures === [] ? 'green' : 'bright-red';
        $this->line("<fg={$failureColor}>❌ failed: " . count($failures) . '</>');

        foreach ($failures as $failure) {
            $this->error(" - {$failure['path']} " . json_encode($failure['params']) . ": {$failure['reason']}");
        }

        $this->line(sprintf('⏱️  Time: <fg=bright-magenta>%.2fs</>', $elapsed));

        if ($failures === []) {
            $this->line('<fg=green>✨ Cache is warm. Ship it! 🚀</>');
        }

        $this->line('');
    }

    /**
     * @param  list<array{file: string, tag: string}>  $dynamic
     */
    private function reportDynamic(array $dynamic): void
    {
        $this->line('⏭️  skipped (dynamic src): ' . count($dynamic));

        foreach ($dynamic as $entry) {
            $tag = (string) preg_replace('/\s+/', ' ', $entry['tag']);
            $tag = strlen($tag) <= 80 ? $tag : substr($tag, 0, 77) . '…';
            $this->line("<fg=gray> - {$entry['file']}: {$tag}</>");
        }
    }
}
