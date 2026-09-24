---
name: glider-development
description: "Use for any task involving images served through daikazu/laravel-glider: rendering <x-glider-img>, <x-glider-img-responsive>, <x-glider-bg>, or <x-glider-bg-responsive> components, glide-* attributes, the Glider facade, image presets and background presets, responsive srcset or sizes, focal points, remote images, watermarks, the glider:build / glider:clear / glider:convert commands, disk-based (S3) caches, static serving, or signed URL / on_the_fly / restrict_to_presets security settings. Also use when converting plain <img> tags to Glider or debugging 400/403/404 image responses under /img. Do not use for general image upload handling or non-Glider media libraries."
license: MIT
metadata:
  author: daikazu
---

# Laravel Glider Development

Laravel Glider wraps League/Glide: images are transformed on request, cached, and served from signed, human-readable URLs like `/img/coins/hero~{token}.webp`. Configuration lives in `config/glider.php` (publish with `php artisan vendor:publish --tag="glider-config"`).

## Core rules

- `src` is a path relative to `glider.source` (default `resource_path('assets')`), or a full `http(s)://` URL for remote images. Do not pass `asset()`/`public_path()` output or a leading `/images/` public prefix.
- Glide manipulation params go on components as `glide-{param}` attributes. Any attribute without the prefix is rendered as a normal HTML attribute (`alt`, `class`, `loading`, ...).
- Never hand-write `/img/...` URLs. The token and signature are generated for you, and an unsigned or tampered URL returns 404 when `glider.secure` is on.
- `glider.defaults` (default `fm=webp`, `q=85`) applies to every image unless overridden.
- Check `config/glider.php` for an existing preset before adding one-off sizes. Add new shared sizes as presets.

## Components

### `<x-glider-img>`

```blade
<x-glider-img src="team/jane.jpg" glide-w="400" glide-h="300" glide-fit="crop" alt="Jane" />
<x-glider-img src="products/{{ $product->image }}" glide-preset="card" alt="{{ $product->name }}" />
<x-glider-img :src="$post->cover_path" glide-preset="hero" alt="" />
```

`width`/`height` attributes are added automatically when the source is on a local filesystem.

### `<x-glider-img-responsive>`

Generates a `srcset`. Widths are calculated from the source image unless you pass `srcset-widths`.

```blade
<x-glider-img-responsive
    src="hero.jpg"
    glide-w="1200"
    srcset-widths="400,800,1200"
    sizes="(min-width: 768px) 50vw, 100vw"
    alt="Hero"
/>
```

- Give it an explicit `sizes` whenever you know the layout slot, so the browser picks the right candidate on the first request.
- Without `sizes`, `loading="lazy"` images get `sizes="auto"`, and eager images get a small `onload` script that sets `sizes` after the first paint.

### `<x-glider-bg>` (single background image)

```blade
<x-glider-bg src="banner.jpg" glide-w="1440" glide-h="600" glide-fit="crop" class="hero">
    <h1>Welcome</h1>
</x-glider-bg>
```

Props: `position` (default `center`), `size` (default `cover`), `repeat` (default `no-repeat`), `attachment` (default `scroll`), `fallback`, and `lazy` (emits `data-bg-lazy`/`data-bg-src` for your own lazy loader). Use `glide-preset` for regular presets here. This component does **not** accept `background_presets`.

### `<x-glider-bg-responsive>` (breakpoint backgrounds)

```blade
<x-glider-bg-responsive src="hero.jpg" preset="hero" class="hero">
    <h1>Content</h1>
</x-glider-bg-responsive>

<x-glider-bg-responsive
    src="banner.jpg"
    :breakpoints="['xs' => ['w' => 768, 'h' => 300], 'lg' => ['w' => 1440, 'h' => 500]]"
>
    ...
</x-glider-bg-responsive>
```

`preset` here is a key in `glider.background_presets`, not in `glider.presets`. It also accepts the same CSS props as `<x-glider-bg>`.

With neither `preset` nor `breakpoints`, built-in breakpoints from 480px to 1920px wide are used. `glide-*` attributes are merged into every breakpoint, but a breakpoint's own params win, so `glide-w` has no effect when the breakpoint sets `w`. An unknown `preset` name throws an `InvalidArgumentException`.

### Focal points

`focus` works on all four components. It accepts `center`, `top`, `bottom`, `left`, `right`, `top-left`, `top-right`, `bottom-left`, `bottom-right`, or `x,y` percentages (`focus="75,25"`). It sets CSS (`object-position` / `background-position`) and does not change the pixels. For a server-side crop, use `glide-fit="crop-top"` or `glide-fit="crop-25-75"` (optionally with zoom: `crop-25-75-2`).

## Facade

```php
use Daikazu\LaravelGlider\Facades\Glider;

$url = Glider::url('photo.jpg', ['w' => 400, 'q' => 85]);
$thumb = Glider::url('photo.jpg', ['preset' => 'thumbnail']);
$remote = Glider::url('https://cdn.example.com/a.jpg', ['w' => 600, 'fm' => 'webp']);
```

Use this for URLs outside Blade components: JSON APIs, Open Graph images, mail, and Livewire/JS payloads.

## Common Glide params

| Param | Values |
|---|---|
| `w`, `h` | pixels |
| `fit` | `contain`, `max`, `fill`, `stretch`, `crop`, `crop-{position}`, `crop-{x}-{y}[-{zoom}]` |
| `q` | 1-100 |
| `fm` | `jpg`, `pjpg`, `png`, `gif`, `webp`, `avif` |
| `blur`, `sharp`, `bri`, `con`, `gam` | effects |
| `filt` | `greyscale`, `sepia` |
| `mark` | watermark path relative to `glider.watermarks` |
| `strip` | `true` removes EXIF/GPS metadata |

Output size is capped by `glider.max_image_size` (total pixels, default 2000x2000).

## Presets

```php
// config/glider.php
'presets' => [
    'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop', 'q' => 90],
],
'background_presets' => [
    'hero' => [
        'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop'],
        'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop'],
    ],
],
```

Use regular presets with `glide-preset="thumbnail"` or `['preset' => 'thumbnail']`. Background presets are keyed by breakpoint name (`xs`, `sm`, `md`, `lg`, `xl`) or pixel width, and are only used by `<x-glider-bg-responsive preset="...">`.

## Artisan commands

- `php artisan glider:build` scans `glider.build.paths` (default `resources/views`) for components and `Glider::url()` calls with a **literal** `src`, then prebuilds those conversions. Dynamic sources (`:src="$var"`, `{{ }}`) are reported as skipped and still work on the fly. Flags: `--dry-run`, and `--static` (bakes into `public/{base_url}` instead of the configured cache). It exits non-zero if any conversion fails.
- `php artisan glider:clear` clears the configured cache (paths and disks). `--static` clears the baked `public/{base_url}` tier instead. `--force` skips the production confirmation.
- `php artisan glider:convert` rewrites plain `<img>` tags to Glider components. **Always run with `--dry-run` first.** Options: `--backup`, `--path=resources/views`, `--responsive`, and `--image-path=/images/` (the public prefix to strip so srcs resolve against `glider.source`; use `--image-path=` to strip nothing). Only literal `src` values and `asset('literal')` are converted.

## Security settings

These three settings are independent and can be combined:

1. `secure` (`GLIDER_SECURE`, default `true`): HMAC-signed URLs using `sign_key` (default `APP_KEY`). Keep it on in production.
2. `on_the_fly` (`GLIDER_ON_THE_FLY`, default `true`): when `false`, cached conversions are served and anything uncached returns **404**. Pair it with `glider:build`. Dynamic `src` values will 404 unless they are already cached.
3. `restrict_to_presets` (`GLIDER_RESTRICT_TO_PRESETS`, default `false`): only preset expansions, defaults-only requests, and format-only changes are allowed. Anything else returns **403**.

Path traversal, CSS injection in background props, and SSRF (private IPs, localhost, metadata endpoints) are blocked automatically. Do not add custom workarounds for them.

### Debugging image responses

- **404**: a missing or invalid signature (a hand-built or edited URL, or a changed `APP_KEY`/`GLIDER_SIGN_KEY`), a source file missing relative to `glider.source`, a failed remote fetch, or `on_the_fly=false` with the conversion not cached.
- **403**: params rejected by `restrict_to_presets`.
- **400**: a path rejected by the traversal/null-byte validator, or invalid Glide params.
- After changing presets or defaults, run `php artisan glider:clear` so stale conversions are regenerated.

## Filesystems and deployment

`source`, `cache`, and `watermarks` each accept a path string or a disk reference (`['disk' => 's3', 'prefix' => 'glider-cache']`). In `.env`, use `GLIDER_CACHE_PATH`, or use `GLIDER_CACHE_DISK` with `GLIDER_CACHE_PREFIX` (the disk form wins). `SOURCE` and `WATERMARKS` follow the same pattern. A non-local `source` disables automatic `width`/`height` and automatic srcset widths, so pass `srcset-widths` explicitly.

Pointing the cache at `public/{base_url}` (e.g. `GLIDER_CACHE_PATH=public/img`) lets the web server serve cached files directly, so PHP only runs for cache misses.

| Situation | Recipe |
|---|---|
| Persistent server(s) | `GLIDER_CACHE_PATH=public/img`, then `glider:build` on deploy |
| Ephemeral/autoscaling, mostly dynamic images | `GLIDER_CACHE_DISK=s3`, then `glider:build` on deploy |
| Ephemeral, mostly static template images | `GLIDER_CACHE_DISK=s3` at runtime, plus `glider:build --static` during the build step |
