<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\FocalPoint;

it('parses named positions and coordinates', function (mixed $in, ?string $out) {
    expect(FocalPoint::parse($in))->toBe($out);
})->with([
    ['center', '50% 50%'], ['top-right', '100% 0%'], ['30,70', '30% 70%'],
    ['150,50', null], ['', null], [null, null], [42, null],
]);

it('parses all named shorthand positions', function () {
    expect(FocalPoint::parse('top'))->toBe('50% 0%')
        ->and(FocalPoint::parse('bottom'))->toBe('50% 100%')
        ->and(FocalPoint::parse('left'))->toBe('0% 50%')
        ->and(FocalPoint::parse('right'))->toBe('100% 50%')
        ->and(FocalPoint::parse('top-left'))->toBe('0% 0%')
        ->and(FocalPoint::parse('bottom-left'))->toBe('0% 100%')
        ->and(FocalPoint::parse('bottom-right'))->toBe('100% 100%');
});

it('is case-insensitive and trims whitespace', function () {
    expect(FocalPoint::parse('  CENTER  '))->toBe('50% 50%')
        ->and(FocalPoint::parse(' 30 , 70 '))->toBe('30% 70%');
});

it('rejects malformed coordinate pairs', function () {
    expect(FocalPoint::parse('30,70,90'))->toBeNull()
        ->and(FocalPoint::parse('-10,50'))->toBeNull()
        ->and(FocalPoint::parse('30,150'))->toBeNull();
});

it('casts non-numeric coordinate parts to zero rather than rejecting them', function () {
    // (int) casting of a non-numeric string yields 0, which is in-range,
    // so this preserves the original component behavior being ported here.
    expect(FocalPoint::parse('abc,def'))->toBe('0% 0%');
});

it('rejects the string "0"', function () {
    expect(FocalPoint::parse('0'))->toBeNull();
});
