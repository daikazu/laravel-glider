<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Components\Bg;
use Daikazu\LaravelGlider\Components\BgResponsive;

describe('XSS Protection in CSS Sanitization', function () {
    it('escapes single quotes in URLs', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSUrl');
        $method->setAccessible(true);

        $result = $method->invoke($component, "test'quote.jpg");
        expect($result)->toBe("test\\'quote.jpg");
    });

    it('escapes backslashes in URLs', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSUrl');
        $method->setAccessible(true);

        $result = $method->invoke($component, "test\\backslash.jpg");
        expect($result)->toContain('\\\\');
    });

    it('escapes both quotes and backslashes', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSUrl');
        $method->setAccessible(true);

        $result = $method->invoke($component, "test'\\both.jpg");
        expect($result)->toContain("\\'");
        expect($result)->toContain('\\\\');
    });

    it('removes dangerous characters from CSS values', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSValue');
        $method->setAccessible(true);

        $result = $method->invoke($component, '<script>alert(1)</script>');
        expect($result)->not->toContain('<');
        expect($result)->not->toContain('>');
        // The word "script" is safe (alphanumeric), and parentheses are allowed for CSS functions
        expect($result)->toBe('scriptalert(1)script');
    });

    it('removes semicolons to prevent CSS injection', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSValue');
        $method->setAccessible(true);

        $result = $method->invoke($component, 'cover; position: fixed; z-index: 999999');
        expect($result)->not->toContain(';');
        expect($result)->not->toContain(':');
        // Words like "position" are safe, only dangerous chars are removed
        expect($result)->toBe('cover position fixed z-index 999999');
    });

    it('allows safe CSS characters', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSValue');
        $method->setAccessible(true);

        $result = $method->invoke($component, 'center top');
        expect($result)->toBe('center top');
    });

    it('allows percentages and parentheses', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSValue');
        $method->setAccessible(true);

        $result = $method->invoke($component, '75% 25%');
        expect($result)->toBe('75% 25%');
    });

    it('allows hyphens and underscores', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSValue');
        $method->setAccessible(true);

        $result = $method->invoke($component, 'top-right');
        expect($result)->toBe('top-right');

        $result2 = $method->invoke($component, 'background_color');
        expect($result2)->toBe('background_color');
    });

    it('handles empty strings safely', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSValue');
        $method->setAccessible(true);

        $result = $method->invoke($component, '');
        expect($result)->toBe('');
    });

    it('BgResponsive component has same sanitization methods', function () {
        $component = new BgResponsive(src: 'test.jpg');
        $reflection = new ReflectionClass($component);

        // Check that both sanitization methods exist
        expect($reflection->hasMethod('sanitizeCSSUrl'))->toBeTrue();
        expect($reflection->hasMethod('sanitizeCSSValue'))->toBeTrue();

        $urlMethod = $reflection->getMethod('sanitizeCSSUrl');
        $urlMethod->setAccessible(true);
        $result = $urlMethod->invoke($component, "test'quote.jpg");
        expect($result)->toBe("test\\'quote.jpg");
    });

    it('removes quotes that could break CSS context', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSUrl');
        $method->setAccessible(true);

        $malicious = "test.jpg');}</style><script>alert('XSS')</script><style>";
        $result = $method->invoke($component, $malicious);

        // Single quotes should all be escaped
        expect($result)->toContain("\\'");
        // The string should not be able to break out of CSS context
        expect(substr_count($result, "\\'"))->toBe(3); // Three single quotes, all escaped
    });

    it('handles complex injection attempts', function () {
        $component = new Bg(src: 'test.jpg');
        $reflection = new ReflectionClass($component);
        $method = $reflection->getMethod('sanitizeCSSValue');
        $method->setAccessible(true);

        $malicious = "center</style><img src=x onerror=alert(1)><style>";
        $result = $method->invoke($component, $malicious);

        // All dangerous characters should be removed
        expect($result)->not->toContain('<');
        expect($result)->not->toContain('>');
        expect($result)->not->toContain('=');
        // The sanitizer removes dangerous chars but keeps safe alphanumeric text and spaces
        expect($result)->toBe('centerstyleimg srcx onerroralert(1)style');
    });
});
