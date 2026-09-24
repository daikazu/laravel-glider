<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;

// Laravel Boost discovers these by path: resources/boost/guidelines/core.{md,blade.php}
// and resources/boost/skills/{name}/SKILL.{md,blade.php}.
$boost = dirname(__DIR__) . '/resources/boost';

it('ships a core Boost guideline', function () use ($boost) {
    expect("{$boost}/guidelines/core.md")->toBeFile();
});

it('ships Boost skills with valid frontmatter', function () use ($boost) {
    $skills = glob("{$boost}/skills/*/SKILL.md");

    expect($skills)->not->toBeEmpty();

    foreach ($skills as $file) {
        preg_match('/\A---\R(.*?)\R---\R/s', (string) file_get_contents($file), $matches);

        expect($matches)->not->toBeEmpty("{$file} has no frontmatter");

        preg_match('/^name:\s*(\S+)\s*$/m', $matches[1], $name);
        preg_match('/^description:\s*"(.+)"\s*$/m', $matches[1], $description);

        // The skill name must match its directory, and the description must fit the Agent Skills limit.
        expect($name[1] ?? null)->toBe(basename(dirname($file)))
            ->and($description[1] ?? '')->not->toBeEmpty()
            ->and(mb_strlen($description[1] ?? ''))->toBeLessThanOrEqual(1024);
    }
});

it('only references components and commands the package registers', function () use ($boost) {
    $content = file_get_contents("{$boost}/guidelines/core.md")
        . implode('', array_map(file_get_contents(...), glob("{$boost}/skills/*/SKILL.md")));

    preg_match_all('/<x-(glider-[a-z-]+)/', $content, $components);
    preg_match_all('/\b(glider:[a-z]+)\b/', $content, $commands);

    foreach (array_unique($components[1]) as $component) {
        expect(Blade::getClassComponentAliases())->toHaveKey($component);
    }

    foreach (array_unique($commands[1]) as $command) {
        expect(Artisan::all())->toHaveKey($command);
    }
});
