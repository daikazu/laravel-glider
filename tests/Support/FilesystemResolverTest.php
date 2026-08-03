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
