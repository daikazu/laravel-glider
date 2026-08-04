<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Components\Bg;
use Daikazu\LaravelGlider\Components\BgResponsive;
use Daikazu\LaravelGlider\Facades\Glider;
use Illuminate\View\ComponentAttributeBag;
use Mockery as m;

afterEach(function () {
    m::close();
});

describe('XSS Protection in CSS Sanitization', function () {
    it('escapes single quotes in the background URL rendered into CSS', function () {
        $originalInstance = Glider::getFacadeRoot();

        $mockService = m::mock();
        $mockService->shouldReceive('getUrl')
            ->andReturn("http://example.com/img/test'quote.jpg");

        Glider::swap($mockService);

        $component = new Bg(src: 'test.jpg');
        $component->attributes = new ComponentAttributeBag;

        $css = $component->backgroundStyle();

        expect($css)->toContain("http://example.com/img/test\\'quote.jpg")
            ->and($css)->not->toContain("test'quote.jpg')");

        Glider::swap($originalInstance);
    });

    it('strips dangerous characters from position/size/repeat/attachment values before they reach the CSS', function () {
        $component = new Bg(
            src: 'test.jpg',
            position: 'center</style><script>alert(1)</script>',
            size: 'cover; position: fixed',
            repeat: 'no-repeat',
            attachment: 'scroll',
        );
        $component->attributes = new ComponentAttributeBag;

        $css = $component->backgroundStyle();

        expect($css)->not->toContain('<script>')
            ->and($css)->not->toContain('</style><script>')
            ->and($css)->not->toContain('position: fixed;')
            ->and($css)->toContain('background-position: centerstylescriptalert(1)script')
            ->and($css)->toContain('background-size: cover position fixed');
    });

    it('escapes quotes for a complex injection attempt end-to-end', function () {
        $originalInstance = Glider::getFacadeRoot();

        $mockService = m::mock();
        $mockService->shouldReceive('getUrl')
            ->andReturn("test.jpg');}</style><script>alert('XSS')</script><style>");

        Glider::swap($mockService);

        $component = new Bg(src: 'test.jpg');
        $component->attributes = new ComponentAttributeBag;

        $css = $component->backgroundStyle();

        // The three single quotes from the malicious URL must all be escaped,
        // so the value can never break out of the CSS `url('...')` context.
        expect(substr_count($css, "\\'"))->toBe(3);

        Glider::swap($originalInstance);
    });

    it('escapes single quotes in BgResponsive-generated CSS the same way as Bg', function () {
        $originalInstance = Glider::getFacadeRoot();

        $mockService = m::mock();
        $mockService->shouldReceive('getUrl')
            ->andReturn("http://example.com/img/test'quote.jpg");

        Glider::swap($mockService);

        $component = new BgResponsive(src: 'test.jpg');
        $component->attributes = new ComponentAttributeBag;

        $css = $component->generateBackgroundCSS();

        expect($css)->toContain("http://example.com/img/test\\'quote.jpg");

        Glider::swap($originalInstance);
    });
});
