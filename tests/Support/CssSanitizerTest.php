<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Support\CssSanitizer;

describe('CssSanitizer::url', function () {
    it('escapes single quotes in URLs', function () {
        expect(CssSanitizer::url("test'quote.jpg"))->toBe("test\\'quote.jpg");
    });

    it('escapes backslashes in URLs', function () {
        expect(CssSanitizer::url('test\\backslash.jpg'))->toContain('\\\\');
    });

    it('escapes both quotes and backslashes', function () {
        $result = CssSanitizer::url("test'\\both.jpg");
        expect($result)->toContain("\\'")
            ->and($result)->toContain('\\\\');
    });

    it('removes quotes that could break CSS context', function () {
        $malicious = "test.jpg');}</style><script>alert('XSS')</script><style>";
        $result = CssSanitizer::url($malicious);

        expect($result)->toContain("\\'")
            ->and(substr_count($result, "\\'"))->toBe(3);
    });
});

describe('CssSanitizer::value', function () {
    it('removes dangerous characters from CSS values', function () {
        $result = CssSanitizer::value('<script>alert(1)</script>');
        expect($result)->not->toContain('<')
            ->and($result)->not->toContain('>')
            ->and($result)->toBe('scriptalert(1)script');
    });

    it('removes semicolons to prevent CSS injection', function () {
        $result = CssSanitizer::value('cover; position: fixed; z-index: 999999');
        expect($result)->not->toContain(';')
            ->and($result)->not->toContain(':')
            ->and($result)->toBe('cover position fixed z-index 999999');
    });

    it('allows safe CSS characters', function () {
        expect(CssSanitizer::value('center top'))->toBe('center top');
    });

    it('allows percentages and parentheses', function () {
        expect(CssSanitizer::value('75% 25%'))->toBe('75% 25%');
    });

    it('allows hyphens and underscores', function () {
        expect(CssSanitizer::value('top-right'))->toBe('top-right')
            ->and(CssSanitizer::value('background_color'))->toBe('background_color');
    });

    it('handles empty strings safely', function () {
        expect(CssSanitizer::value(''))->toBe('');
    });

    it('handles complex injection attempts', function () {
        $malicious = 'center</style><img src=x onerror=alert(1)><style>';
        $result = CssSanitizer::value($malicious);

        expect($result)->not->toContain('<')
            ->and($result)->not->toContain('>')
            ->and($result)->not->toContain('=')
            ->and($result)->toBe('centerstyleimg srcx onerroralert(1)style');
    });
});
