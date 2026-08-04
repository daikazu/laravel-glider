<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ConvertImageTagsToGliderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glider:convert
                            {--dry-run : Show what would be changed without making any changes}
                            {--backup : Create backup files before making changes}
                            {--path=resources/views : Path to search for blade files}
                            {--responsive : Convert to responsive glider components by default}
                            {--image-path=/images/ : Default image path to strip from src attributes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert HTML img tags to Laravel Glider components (⚠️ USE AT YOUR OWN RISK - Run with --dry-run first)';

    /**
     * A src that is entirely `{{ asset('<string literal>') }}` — the only
     * blade-echo form that is statically resolvable.
     */
    private const string STATIC_ASSET_PATTERN = '/^\{\{\s*asset\(\s*(["\'])([^"\']+)\1\s*\)\s*\}\}$/';

    private array $changedFiles = [];
    private array $totalChanges = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $searchPath = $this->option('path');
        $isDryRun = $this->option('dry-run');
        $shouldBackup = $this->option('backup');
        $useResponsive = $this->option('responsive');
        $imagePath = $this->option('image-path');

        if (! is_dir($searchPath)) {
            $this->error("Directory {$searchPath} does not exist.");
            return 1;
        }

        // Display warning and confirmation unless in dry-run mode
        if (! $isDryRun) {
            $this->warn('⚠️  USE AT YOUR OWN RISK ⚠️');
            $this->newLine();
            $this->line('This command will modify your Blade files by converting <img> tags to Laravel Glider components.');
            $this->newLine();
            $this->info('💡 Recommended safety steps before proceeding:');
            $this->line('   1. Create a git commit of your current work');
            $this->line('   2. OR create a new branch to review changes:');
            $this->line('      <fg=cyan>git checkout -b glider-conversion</>');
            $this->line('   3. Run this command first with --dry-run to preview changes:');
            $this->line('      <fg=cyan>php artisan glider:convert --dry-run</>');
            $this->line('   4. Use --backup to create timestamped backups of modified files');
            $this->newLine();

            if (! $this->confirm('Do you want to continue?', false)) {
                $this->info('Operation cancelled. No files were modified.');
                return 0;
            }
        }

        $this->info("Searching for Blade files in: {$searchPath}");
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No files will be modified');
        }

        $bladeFiles = $this->getBladeFiles($searchPath);

        if ($bladeFiles === []) {
            $this->info('No Blade files found.');
            return 0;
        }

        $this->info('Found ' . count($bladeFiles) . ' Blade files to process...');

        foreach ($bladeFiles as $filePath) {
            $this->processFile($filePath, $isDryRun, $shouldBackup, $useResponsive, $imagePath);
        }

        $this->displaySummary($isDryRun);

        return 0;
    }

    /**
     * Get all Blade files recursively from the given path
     */
    private function getBladeFiles(string $path): array
    {
        $files = File::allFiles($path);

        return collect($files)
            ->filter(fn ($file): bool => $file->getExtension() === 'php' && str_ends_with((string) $file->getFilename(), '.blade.php'))
            ->map(fn ($file) => $file->getPathname())
            ->toArray();
    }

    /**
     * Process a single file for image tag conversion
     */
    private function processFile(string $filePath, bool $isDryRun, bool $shouldBackup, bool $useResponsive, string $imagePath): void
    {
        $originalContent = File::get($filePath);
        $modifiedContent = $this->convertImageTags($originalContent, $useResponsive, $imagePath);

        if ($originalContent === $modifiedContent) {
            return; // No changes needed
        }

        $relativePath = str_replace(base_path() . '/', '', $filePath);
        $this->changedFiles[] = $relativePath;

        if ($isDryRun) {
            $this->line("<fg=yellow>Would modify:</fg=yellow> {$relativePath}");
            return;
        }

        // Create backup if requested
        if ($shouldBackup) {
            $backupPath = $filePath . '.backup.' . date('Y-m-d-H-i-s');
            File::copy($filePath, $backupPath);
            $this->line("<fg=blue>Backup created:</fg=blue> {$backupPath}");
        }

        // Write the modified content
        File::put($filePath, $modifiedContent);
        $this->line("<fg=green>Modified:</fg=green> {$relativePath}");
    }

    /**
     * Convert HTML img tags to Laravel Glider components
     */
    private function convertImageTags(string $content, bool $useResponsive, string $imagePath): string
    {
        // Match whole <img> tags; quoted sections may contain ">" safely.
        $pattern = '/<img\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*?)\/?>/i';

        $result = preg_replace_callback($pattern, function (array $matches) use ($useResponsive, $imagePath): string {
            $attributes = $this->parseAttributes($matches[1]);

            $src = null;
            foreach ($attributes as $attribute) {
                if ($attribute['name'] === ':src') {
                    return $matches[0]; // dynamic binding — leave untouched
                }

                if (strtolower($attribute['name']) === 'src') {
                    $src = $attribute['value'];
                }
            }

            // No literal, statically-resolvable src: leave the tag untouched.
            // Blade-echo srcs are dynamic, except when the whole expression is
            // asset() of a pure string literal — concatenations and variables
            // inside asset() are still dynamic.
            if ($src === null || (str_contains($src, '{{') && preg_match(self::STATIC_ASSET_PATTERN, trim($src)) !== 1)) {
                return $matches[0];
            }

            $cleanSrc = $this->cleanSrcValue($src, $imagePath);
            $componentType = $useResponsive ? 'x-glider-img-responsive' : 'x-glider-img';

            // src first, every other attribute in original order and quoting
            // that survives its content (values may hold the other quote type).
            $parts = ['src="' . $cleanSrc . '"'];

            foreach ($attributes as $attribute) {
                if (strtolower($attribute['name']) === 'src') {
                    continue;
                }

                if ($attribute['value'] === null) {
                    $parts[] = $attribute['name']; // boolean attribute
                    continue;
                }

                $quote = str_contains($attribute['value'], '"') ? "'" : '"';
                $parts[] = $attribute['name'] . '=' . $quote . $attribute['value'] . $quote;
            }

            $converted = '<' . $componentType . ' ' . implode(' ', $parts) . ' />';

            $this->totalChanges[] = [
                'from' => $matches[0],
                'to'   => $converted,
            ];

            return $converted;
        }, $content);

        return $result ?? $content;
    }

    /**
     * Parse a tag's attribute blob preserving order, hyphenated/bound names,
     * boolean attributes, and values containing the other quote type.
     *
     * @return list<array{name: string, value: ?string}>
     */
    private function parseAttributes(string $attributeString): array
    {
        preg_match_all(
            '/(?<name>[:@a-zA-Z0-9_.-]+)(?:\s*=\s*(?:"(?<dq>[^"]*)"|\'(?<sq>[^\']*)\'|(?<uq>[^\s"\'>]+)))?/',
            $attributeString,
            $matches,
            PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL
        );

        $attributes = [];

        foreach ($matches as $match) {
            $attributes[] = [
                'name'  => $match['name'],
                'value' => $match['dq'] ?? $match['sq'] ?? $match['uq'] ?? null,
            ];
        }

        return $attributes;
    }

    /**
     * Clean the src value - preserve original content for maximum versatility
     */
    private function cleanSrcValue(string $srcValue, string $imagePath): string
    {
        // Remove asset() wrapper (only the pure string-literal form gets here)
        if (preg_match(self::STATIC_ASSET_PATTERN, trim($srcValue), $matches)) {
            $path = $matches[2];
            // Remove leading /images/ if present since glider handles this
            return ltrim(str_replace($imagePath, '', $path), '/');
        }

        // Handle direct paths
        if (str_starts_with($srcValue, $imagePath)) {
            return ltrim(str_replace($imagePath, '', $srcValue), '/');
        }

        // Return as-is for external URLs or other formats
        return $srcValue;
    }

    /**
     * Display summary of changes
     */
    private function displaySummary(bool $isDryRun): void
    {
        $this->newLine();

        if ($this->changedFiles === []) {
            $this->info('No image tags found to convert.');
            return;
        }

        $fileCount = count($this->changedFiles);
        $changeCount = count($this->totalChanges);

        $action = $isDryRun ? 'Would convert' : 'Converted';
        $this->info("{$action} {$changeCount} image tag(s) in {$fileCount} file(s):");

        foreach ($this->changedFiles as $file) {
            $this->line("  - {$file}");
        }

        if (! $isDryRun && $this->totalChanges !== []) {
            $this->newLine();
            $this->info('Examples of changes made:');

            // Show first 3 examples
            $examples = array_slice($this->totalChanges, 0, 3);
            foreach ($examples as $change) {
                $this->line('<fg=red>From:</fg=red> ' . $change['from']);
                $this->line('<fg=green>To:</fg=green>   ' . $change['to']);
                $this->newLine();
            }

            if (count($this->totalChanges) > 3) {
                $remaining = count($this->totalChanges) - 3;
                $this->line("... and {$remaining} more changes.");
            }

            $this->newLine();
            $this->info('✅ Conversion complete!');
            $this->newLine();
            $this->info('📝 Next steps:');
            $this->line('   1. Review the changes in your files');
            $this->line('   2. Test your application to ensure images load correctly');
            $this->line('   3. If using git, review changes with: <fg=cyan>git diff</>');
            $this->line('   4. Commit the changes when satisfied: <fg=cyan>git add . && git commit -m "Convert img tags to Glider components"</>');
        } elseif ($isDryRun && $this->totalChanges !== []) {
            $this->newLine();
            $this->info('Examples of changes that would be made:');

            // Show first 3 examples
            $examples = array_slice($this->totalChanges, 0, 3);
            foreach ($examples as $change) {
                $this->line('<fg=red>From:</fg=red> ' . $change['from']);
                $this->line('<fg=green>To:</fg=green>   ' . $change['to']);
                $this->newLine();
            }

            if (count($this->totalChanges) > 3) {
                $remaining = count($this->totalChanges) - 3;
                $this->line("... and {$remaining} more changes.");
            }

            $this->newLine();
            $this->info('💡 To apply these changes, run the command without --dry-run');
            $this->line('   <fg=cyan>php artisan glider:convert</>');
            $this->line('   OR with --backup to create backups:');
            $this->line('   <fg=cyan>php artisan glider:convert --backup</>');
        }
    }
}
