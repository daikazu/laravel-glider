<?php

declare(strict_types=1);

use Daikazu\LaravelGlider\Build\BladeUsage;

/**
 * Job params arrive in controller-restoration order (fm appended last);
 * comparisons only care about the key-value pairs.
 */
function canonicalizeJobs(array $jobs): array
{
    return array_map(function (array $job): array {
        ksort($job['params']);

        return $job;
    }, $jobs);
}

use Daikazu\LaravelGlider\Build\ConversionResolver;

beforeEach(function () {
    config()->set('glider.source', __DIR__ . '/../fixtures');
});

it('resolves an img usage to one job with the preset fully expanded', function () {
    // The job's params must be the *fully resolved* Glide params a live
    // request would end up with — not just `preset` renamed to `p`. Glide
    // expands `p` into its constituent params (and discards `p` itself)
    // while computing the cache path, and merges in `glider.defaults`, so
    // anything short of that full expansion warms the wrong cache entry.
    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('img', 'hero.jpg', ['glide-w' => '1200', 'glide-preset' => 'thumbnail'], 'a.blade.php')
    );

    expect(canonicalizeJobs($jobs))->toBe([[
        'path'   => 'hero.jpg',
        'params' => ['fit' => 'crop', 'fm' => 'webp', 'h' => '150', 'q' => '90', 'w' => '1200'],
    ]]);
});

it('resolves a bg usage to one job with glide- prefix stripped and defaults merged in', function () {
    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('bg', 'banner.jpg', ['glide-fit' => 'crop'], 'a.blade.php')
    );

    expect(canonicalizeJobs($jobs))->toBe([[
        'path'   => 'banner.jpg',
        'params' => ['fit' => 'crop', 'fm' => 'webp', 'q' => '85'],
    ]]);
});

it('resolves a url usage to one job with attributes as-is plus defaults merged in', function () {
    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('url', 'inline.jpg', ['w' => '400', 'fm' => 'webp'], 'a.blade.php')
    );

    expect(canonicalizeJobs($jobs))->toBe([[
        'path'   => 'inline.jpg',
        'params' => ['fm' => 'webp', 'q' => '85', 'w' => '400'],
    ]]);
});

it('resolves img-responsive to one job per srcset width plus the base image', function () {
    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('img-responsive', 'test-tiny.jpg', ['srcset-widths' => '400,800'], 'a.blade.php')
    );
    $widths = collect($jobs)->pluck('params.w')->filter()->values();
    expect($widths->all())->toBe(['400', '800'])->and($jobs)->toHaveCount(3);
});

it('resolves img-responsive with a preset to jobs with the preset fully expanded', function () {
    // Regression test for the bug found in review: imgResponsiveJobs()
    // never mapped `preset` to `p`, so a `glide-preset` attribute survived
    // as a raw, meaningless `preset` key and its params were silently
    // dropped rather than applied.
    config()->set('glider.presets.thumbnail', ['w' => 150, 'h' => 150, 'fit' => 'crop', 'q' => 90]);

    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('img-responsive', 'test-tiny.jpg', ['glide-preset' => 'thumbnail', 'srcset-widths' => '10'], 'a.blade.php')
    );

    expect(canonicalizeJobs($jobs))->toBe([
        [
            'path' => 'test-tiny.jpg',
            // Explicit srcset `w`/`q`/`fm` override the preset's values.
            'params' => ['fit' => 'crop', 'fm' => 'webp', 'h' => '150', 'q' => '85', 'w' => '10'],
        ],
        [
            'path' => 'test-tiny.jpg',
            // The plain `src()` job has nothing to override the preset with.
            'params' => ['fit' => 'crop', 'fm' => 'webp', 'h' => '150', 'q' => '90', 'w' => '150'],
        ],
    ]);
});

it('resolves bg-responsive presets to one job per breakpoint', function () {
    config()->set('glider.background_presets.hero', [
        'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop'],
        'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop'],
    ]);
    expect(app(ConversionResolver::class)->jobs(
        new BladeUsage('bg-responsive', 'banner.jpg', ['preset' => 'hero'], 'a.blade.php')
    ))->toHaveCount(2);
});

it('resolves a usage whose src path happens to contain the base_url segment to no jobs when directly servable', function () {
    // Regression test for the bug found in review: the old Glide-route
    // detection was a naive `str_contains($urlPath, '/img/')` substring
    // scan. `base_url` defaults to "img", and "img/..." is a common asset
    // layout, so a direct-serve URL like ".../storage/img/plain.jpg"
    // contains "/img/" as a substring even though it never touches the
    // Glide route at all. The check must be anchored to the *start* of
    // the path and derived from the real route, not a substring match.
    config()->set('glider.source', storage_path('app/public'));
    config()->set('filesystems.disks.public.root', storage_path('app/public'));

    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('img', 'img/plain.jpg', [], 'a.blade.php')
    );

    expect($jobs)->toBe([]);
});

it('resolves a usage whose src would be served directly (no manipulation) to no jobs', function () {
    // When params are empty and the source path is directly servable
    // (public disk / storage passthrough), `Glider::url()` returns a plain
    // asset URL rather than a Glide route URL. There is nothing to
    // prebuild in that case since a live request never hits the Glide
    // route either.
    config()->set('glider.source', storage_path('app/public'));
    config()->set('filesystems.disks.public.root', storage_path('app/public'));

    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('img', 'plain.jpg', [], 'a.blade.php')
    );

    expect($jobs)->toBe([]);
});

it('mirrors srcset q/fm overrides in img-responsive build candidates', function () {
    // ImgResponsive::srcset() treats q=85/fm=webp as DEFAULTS that the
    // user's glide-q/glide-fm override — the build resolver must mirror
    // that exactly or the cache-equivalence invariant breaks.
    config()->set('glider.source', __DIR__ . '/../fixtures');

    $jobs = app(ConversionResolver::class)->jobs(
        new BladeUsage('img-responsive', 'test-tiny.jpg', ['glide-q' => '50', 'glide-fm' => 'png', 'srcset-widths' => '10'], 'a.blade.php')
    );

    $widthJob = collect($jobs)->first(fn (array $job): bool => ($job['params']['w'] ?? null) === '10');

    expect($widthJob)->not->toBeNull()
        ->and($widthJob['params']['q'])->toBe('50')
        ->and($widthJob['params']['fm'])->toBe('png');
});
