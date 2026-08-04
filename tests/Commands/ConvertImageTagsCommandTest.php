<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->viewsDir = sys_get_temp_dir() . '/glider-convert-test';
    File::deleteDirectory($this->viewsDir);
    File::ensureDirectoryExists($this->viewsDir);
});

afterEach(function () {
    File::deleteDirectory($this->viewsDir);
});

function convertFixture(string $blade): string
{
    $path = test()->viewsDir . '/page.blade.php';
    File::put($path, $blade);

    test()->artisan('glider:convert', ['--path' => test()->viewsDir])
        ->expectsConfirmation('Do you want to continue?', 'yes')
        ->assertSuccessful();

    return File::get($path);
}

it('converts a simple img tag, stripping the image path prefix', function () {
    expect(convertFixture('<img src="/images/hero.jpg" alt="Hero" class="w-full">'))
        ->toBe('<x-glider-img src="hero.jpg" alt="Hero" class="w-full" />');
});

it('strips an asset() wrapper from the src', function () {
    expect(convertFixture('<img src="{{ asset(\'/images/logo.png\') }}" alt="Logo">'))
        ->toBe('<x-glider-img src="logo.png" alt="Logo" />');
});

it('strips the image path prefix regardless of leading slashes', function (string $blade, string $expected) {
    expect(convertFixture($blade))->toBe($expected);
})->with([
    'asset without leading slash' => [
        '<img src="{{ asset(\'images/theme/logo-teejay-3d.png\') }}" alt="TJM Logo" aria-label="TJM Logo" class="max-h-20" />',
        '<x-glider-img src="theme/logo-teejay-3d.png" alt="TJM Logo" aria-label="TJM Logo" class="max-h-20" />',
    ],
    'direct src without leading slash' => [
        '<img src="images/a.jpg" alt="x">',
        '<x-glider-img src="a.jpg" alt="x" />',
    ],
    'non-matching prefix keeps path' => [
        '<img src="/uploads/b.jpg" alt="x">',
        '<x-glider-img src="uploads/b.jpg" alt="x" />',
    ],
]);

it('leaves external URLs as-is', function () {
    expect(convertFixture('<img src="https://example.com/images/x.jpg" alt="x">'))
        ->toBe('<x-glider-img src="https://example.com/images/x.jpg" alt="x" />');
});

it('strips a custom --image-path prefix', function () {
    File::put($this->viewsDir . '/page.blade.php', '<img src="/assets/photos/a.jpg">');

    $this->artisan('glider:convert', ['--path' => $this->viewsDir, '--image-path' => 'assets/'])
        ->expectsConfirmation('Do you want to continue?', 'yes')
        ->assertSuccessful();

    expect(File::get($this->viewsDir . '/page.blade.php'))
        ->toBe('<x-glider-img src="photos/a.jpg" />');
});

it('strips nothing when --image-path is empty', function () {
    File::put($this->viewsDir . '/page.blade.php', '<img src="/images/a.jpg">');

    $this->artisan('glider:convert', ['--path' => $this->viewsDir, '--image-path' => ''])
        ->expectsConfirmation('Do you want to continue?', 'yes')
        ->assertSuccessful();

    expect(File::get($this->viewsDir . '/page.blade.php'))
        ->toBe('<x-glider-img src="images/a.jpg" />');
});

it('leaves non-asset blade helper srcs untouched', function (string $blade) {
    expect(convertFixture($blade))->toBe($blade);
})->with([
    'url helper'  => '<img src="{{ url(\'images/x.jpg\') }}" alt="x">',
    'vite asset'  => '<img src="{{ Vite::asset(\'resources/images/x.jpg\') }}" alt="x">',
    'storage url' => '<img src="{{ Storage::url(\'x.jpg\') }}" alt="x">',
]);

it('preserves hyphenated, boolean, and blade-expression attributes', function () {
    $result = convertFixture('<img src="/images/a.jpg" data-lazy="1" aria-label="photo" hidden alt="{{ $style[\'name\'] }}">');

    expect($result)->toBe('<x-glider-img src="a.jpg" data-lazy="1" aria-label="photo" hidden alt="{{ $style[\'name\'] }}" />');
});

it('uses the real src attribute, not data-src', function () {
    expect(convertFixture('<img data-src="lazy.jpg" src="/images/real.jpg">'))
        ->toBe('<x-glider-img src="real.jpg" data-src="lazy.jpg" />');
});

it('leaves dynamic sources untouched', function (string $blade) {
    expect(convertFixture($blade))->toBe($blade);
})->with([
    'bound src'          => '<img :src="$post->image" alt="x">',
    'blade echo src'     => '<img src="{{ $hero }}" alt="x">',
    'no src'             => '<img alt="decorative">',
    'concatenated asset' => '<img src="{{ asset(\'images/\' . $item[\'image\']) }}" alt="x">',
    'asset of variable'  => '<img src="{{ asset($logo) }}" alt="x">',
]);

it('converts to the responsive component with --responsive', function () {
    File::put($this->viewsDir . '/page.blade.php', '<img src="/images/a.jpg">');

    $this->artisan('glider:convert', ['--path' => $this->viewsDir, '--responsive' => true])
        ->expectsConfirmation('Do you want to continue?', 'yes')
        ->assertSuccessful();

    expect(File::get($this->viewsDir . '/page.blade.php'))
        ->toBe('<x-glider-img-responsive src="a.jpg" />');
});

it('makes no changes in dry-run mode', function () {
    $blade = '<img src="/images/a.jpg">';
    File::put($this->viewsDir . '/page.blade.php', $blade);

    $this->artisan('glider:convert', ['--path' => $this->viewsDir, '--dry-run' => true])
        ->expectsOutputToContain('Would modify:')
        ->assertSuccessful();

    expect(File::get($this->viewsDir . '/page.blade.php'))->toBe($blade);
});

it('creates a backup with --backup', function () {
    File::put($this->viewsDir . '/page.blade.php', '<img src="/images/a.jpg">');

    $this->artisan('glider:convert', ['--path' => $this->viewsDir, '--backup' => true])
        ->expectsConfirmation('Do you want to continue?', 'yes')
        ->assertSuccessful();

    $backups = glob($this->viewsDir . '/page.blade.php.backup.*');
    expect($backups)->toHaveCount(1)
        ->and(File::get($backups[0]))->toBe('<img src="/images/a.jpg">');
});

it('handles multi-line img tags', function () {
    $result = convertFixture("<img\n    src=\"/images/a.jpg\"\n    alt=\"Multi\"\n    loading=\"lazy\"\n>");

    expect($result)->toBe('<x-glider-img src="a.jpg" alt="Multi" loading="lazy" />');
});
