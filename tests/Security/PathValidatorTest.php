<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Security\PathValidator;

it('rejects traversal and null bytes', function (string $path) {
    (new PathValidator(config('glider.source')))->validate($path);
})->with(['../secret.txt', "file\0.jpg", '..\\windows.jpg'])->throws(InvalidArgumentException::class);

it('accepts a clean relative path', function () {
    (new PathValidator(sys_get_temp_dir()))->validate('images/ok.jpg');
})->throwsNoExceptions();
