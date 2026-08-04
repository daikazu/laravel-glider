<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\FilesystemResolver;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemOperator;

it('resolves a plain path to a local filesystem', function () {
    $fs = (new FilesystemResolver)->resolve(sys_get_temp_dir());
    expect($fs)->toBeInstanceOf(FilesystemOperator::class);
});

it('resolves a disk reference through Storage', function () {
    Storage::fake('assets');
    Storage::disk('assets')->put('glider/a.txt', 'hi');
    $fs = (new FilesystemResolver)->resolve(['disk' => 'assets', 'prefix' => 'glider']);
    expect($fs->read('a.txt'))->toBe('hi');
});

it('returns a local path for path config and local disks, null otherwise', function () {
    Storage::fake('assets');
    $r = new FilesystemResolver;
    expect($r->localPath('/tmp/imgs'))->toBe('/tmp/imgs')
        ->and($r->localPath(['disk' => 'assets']))->toBeString();
});

it('anchors relative string paths to the application base path', function () {
    $r = new FilesystemResolver;
    expect($r->localPath('public/glider'))->toBe(base_path('public/glider'));
});

it('resolves relative string paths independent of the current working directory', function () {
    $dir = base_path('glider-rel-test');
    @mkdir($dir, 0755, true);
    file_put_contents($dir . '/probe.txt', 'found');

    $cwd = getcwd();
    chdir(sys_get_temp_dir());

    try {
        $fs = (new FilesystemResolver)->resolve('glider-rel-test');
        expect($fs->fileExists('probe.txt'))->toBeTrue()
            ->and($fs->read('probe.txt'))->toBe('found');
    } finally {
        chdir((string) $cwd);
        @unlink($dir . '/probe.txt');
        @rmdir($dir);
    }
});
