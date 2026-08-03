<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Security\UrlValidator;

it('rejects SSRF targets', function (string $url) {
    (new UrlValidator)->validate($url);
})->with([
    'http://localhost/x.jpg', 'http://127.0.0.1/x.jpg', 'http://169.254.169.254/x.jpg',
    'ftp://example.com/x.jpg', 'https://example.com:6379/x.jpg',
])->throws(InvalidArgumentException::class);
