<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Build;

use Symfony\Component\Finder\Finder;

/**
 * Scans Blade templates for glider component tags and `Glider::url()`
 * facade calls, splitting them into statically-resolvable usages (which
 * the prebuild command can pre-warm) and dynamic usages (which depend on
 * runtime data and can only be reported to the user).
 */
final class TemplateScanner
{
    /**
     * Longest-name-first alternation so `img-responsive` / `bg-responsive`
     * are matched before the shorter `img` / `bg`.
     */
    private const string TAG_PATTERN = '/<x-glider-(img-responsive|img|bg-responsive|bg)\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*?)\/?>/s';

    private const string ATTRIBUTE_PATTERN = '/(?<name>:?[a-zA-Z0-9_-]+)\s*=\s*(?:"(?<dq>[^"]*)"|\'(?<sq>[^\']*)\')/';

    private const string FACADE_CALL_PATTERN = '/Glider::url\([^)]*\)/s';

    private const string FACADE_LITERAL_PATTERN = '/Glider::url\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*\[(.*?)\])?\s*\)/s';

    private const string FACADE_PAIR_PATTERN = '/[\'"](\w+)[\'"]\s*=>\s*[\'"]?([\w.\-]+)[\'"]?/';

    /**
     * @param  list<string>  $paths
     * @return array{usages: list<BladeUsage>, dynamic: list<array{file: string, tag: string}>}
     */
    public function scan(array $paths): array
    {
        $usages = [];
        $dynamic = [];

        $existingPaths = array_values(array_filter($paths, is_dir(...)));

        if ($existingPaths === []) {
            return ['usages' => $usages, 'dynamic' => $dynamic];
        }

        $finder = new Finder;
        $finder->files()->in($existingPaths)->name('*.blade.php');

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $filePath = $file->getPathname();

            $this->scanComponentTags($contents, $filePath, $usages, $dynamic);
            $this->scanFacadeCalls($contents, $filePath, $usages, $dynamic);
        }

        return ['usages' => $usages, 'dynamic' => $dynamic];
    }

    /**
     * @param  list<BladeUsage>  $usages
     * @param  list<array{file: string, tag: string}>  $dynamic
     */
    private function scanComponentTags(string $contents, string $filePath, array &$usages, array &$dynamic): void
    {
        if (preg_match_all(self::TAG_PATTERN, $contents, $matches, PREG_SET_ORDER) === false) {
            return;
        }

        foreach ($matches as $match) {
            $fullTag = trim($match[0]);
            $component = $match[1];
            $attrs = $this->parseAttributes($match[2]);

            if (array_key_exists(':src', $attrs)) {
                $dynamic[] = ['file' => $filePath, 'tag' => $fullTag];

                continue;
            }

            if (! array_key_exists('src', $attrs)) {
                $dynamic[] = ['file' => $filePath, 'tag' => $fullTag];

                continue;
            }

            $src = $attrs['src'];

            if (str_contains($src, '{{')) {
                $dynamic[] = ['file' => $filePath, 'tag' => $fullTag];

                continue;
            }

            unset($attrs['src']);

            $usages[] = new BladeUsage($component, $src, $attrs, $filePath);
        }
    }

    /**
     * @param  list<BladeUsage>  $usages
     * @param  list<array{file: string, tag: string}>  $dynamic
     */
    private function scanFacadeCalls(string $contents, string $filePath, array &$usages, array &$dynamic): void
    {
        if (preg_match_all(self::FACADE_CALL_PATTERN, $contents, $matches) === false) {
            return;
        }

        foreach ($matches[0] as $callText) {
            if (preg_match(self::FACADE_LITERAL_PATTERN, $callText, $literalMatch) === 1 && $literalMatch[0] === $callText) {
                $src = $literalMatch[1];
                $paramsRaw = $literalMatch[2] ?? '';
                $attributes = $this->parseFacadeParams($paramsRaw);

                $usages[] = new BladeUsage('url', $src, $attributes, $filePath);

                continue;
            }

            $dynamic[] = ['file' => $filePath, 'tag' => trim($callText)];
        }
    }

    /**
     * @return array<string, string>
     */
    private function parseAttributes(string $attributeString): array
    {
        $attributes = [];

        if (preg_match_all(self::ATTRIBUTE_PATTERN, $attributeString, $matches, PREG_SET_ORDER) === false) {
            return $attributes;
        }

        foreach ($matches as $match) {
            $name = $match['name'];
            $dq = $match['dq'] ?? '';
            $sq = $match['sq'] ?? '';
            $value = $dq !== '' ? $dq : $sq;

            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    private function parseFacadeParams(string $paramsRaw): array
    {
        $attributes = [];

        if ($paramsRaw === '') {
            return $attributes;
        }

        if (preg_match_all(self::FACADE_PAIR_PATTERN, $paramsRaw, $matches, PREG_SET_ORDER) === false) {
            return $attributes;
        }

        foreach ($matches as $match) {
            $attributes[$match[1]] = $match[2];
        }

        return $attributes;
    }
}
