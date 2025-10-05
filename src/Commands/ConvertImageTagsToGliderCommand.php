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
    protected $signature = 'glider:convert-img-tags
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
            $this->line('      <fg=cyan>php artisan glider:convert-img-tags --dry-run</>');
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
        // Pattern to match <img> tags with src attribute, handling nested quotes in Blade syntax
        $pattern = '/<img\s+([^>]*?)src=(["\'])((?:(?!\2).)*)\2([^>]*?)>/i';

        return preg_replace_callback($pattern, function (array $matches) use ($useResponsive, $imagePath): string {
            $beforeSrc = trim($matches[1]);
            $srcValue = $matches[3]; // The actual src value is now in group 3
            $afterSrc = trim($matches[4]); // After src attributes are in group 4

            // Extract all attributes from the original img tag
            $allAttributes = $this->extractAllAttributes($beforeSrc . ' ' . $afterSrc);

            // Clean up the src value
            $cleanSrc = $this->cleanSrcValue($srcValue, $imagePath);

            // Build the glider component with all original attributes preserved
            $componentType = $useResponsive ? 'x-glide-img-responsive' : 'x-glide-img';

            // Start with the cleaned src
            $attributes = ['src="' . $cleanSrc . '"'];

            // Add all other original attributes
            foreach ($allAttributes as $attr) {
                if (! str_starts_with(strtolower($attr), 'src=')) {
                    $attributes[] = $attr;
                }
            }

            $result = '<' . $componentType . ' ' . implode(' ', $attributes) . ' />';

            // Track this change
            $this->totalChanges[] = [
                'from' => $matches[0],
                'to'   => $result,
            ];

            return $result;
        }, $content);
    }

    /**
     * Extract all attributes from the attribute string
     */
    private function extractAllAttributes(string $attributeString): array
    {
        $attributes = [];
        $pattern = '/(\w+)=["\']([^"\']*?)["\']/';

        preg_match_all($pattern, $attributeString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $attributes[] = $match[1] . '="' . $match[2] . '"';
        }

        return $attributes;
    }

    /**
     * Clean the src value - preserve original content for maximum versatility
     */
    private function cleanSrcValue(string $srcValue, string $imagePath): string
    {
        // Remove asset() wrapper
        if (preg_match('/asset\(["\'](.+?)["\']/', $srcValue, $matches)) {
            $path = $matches[1];
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
            $this->line('   <fg=cyan>php artisan glider:convert-img-tags</>');
            $this->line('   OR with --backup to create backups:');
            $this->line('   <fg=cyan>php artisan glider:convert-img-tags --backup</>');
        }
    }
}
