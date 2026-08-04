# Upgrade Guide

## Upgrading from v3 to v4

Laravel Glider v4 is a breaking release. It renames the package's public
surface from `glide` to `glider` for consistency (the composer package name
and PHP namespace were already `laravel-glider` / `Daikazu\LaravelGlider`,
so this finishes the rename everywhere else), introduces a new
human-readable image URL format (see section 8), raises the minimum
platform requirements, and adds several new opt-in features. Image
transformation itself is unchanged — the same source and parameters produce
the same pixels — but the URLs that address those transformations are new,
so previously issued v3 URLs no longer resolve.

### 1. Check requirements

v4 requires:

- PHP ^8.3
- Laravel 13 only (`illuminate/contracts` ^13.0) — Laravel 11 and 12 are no
  longer supported
- `league/glide` ^4.1 (pulls in Intervention Image v4)

If you're on Laravel 11 or 12, upgrade Laravel first, or stay on
`laravel-glider` v3 until you can move to Laravel 13.

### 2. Update composer

```bash
composer require daikazu/laravel-glider:^4.0
```

### 3. Rename the config file

The config file moved from `config/laravel-glider.php` to `config/glider.php`,
and every key inside it moved from the `laravel-glider.*` namespace to the
`glider.*` namespace (e.g. `config('laravel-glider.source')` is now
`config('glider.source')`).

Delete your old published config and re-publish the new one, then port over
any customizations (presets, background presets, source/cache paths, etc.):

```bash
rm config/laravel-glider.php
php artisan vendor:publish --tag="glider-config"
```

Search your codebase for `config('laravel-glider` / `Config::get('laravel-glider` and update to `config('glider`.

### 4. Rename environment variables

Every `GLIDE_*` environment variable was renamed to `GLIDER_*`. Update your
`.env` files (and any `.env.example`, CI secrets, deployment configs, etc.):

| v3 | v4 |
|---|---|
| `GLIDE_BASE_URL` | `GLIDER_BASE_URL` |
| `GLIDE_SOURCE_PATH` | `GLIDER_SOURCE_PATH` |
| `GLIDE_WATERMARKS_PATH` | `GLIDER_WATERMARKS_PATH` |
| `GLIDE_CACHE_PATH` | `GLIDER_CACHE_PATH` |
| `GLIDE_CACHE_WITH_EXTENSIONS` | `GLIDER_CACHE_WITH_EXTENSIONS` |
| `GLIDE_SECURE` | `GLIDER_SECURE` |
| `GLIDE_SIGN_KEY` | `GLIDER_SIGN_KEY` |
| `GLIDE_GROUP_CACHE` | `GLIDER_GROUP_CACHE` |
| `GLIDE_MAX_IMAGE_SIZE` | `GLIDER_MAX_IMAGE_SIZE` |
| `GLIDE_IMAGE_MANIPULATION_DRIVER` | `GLIDER_IMAGE_MANIPULATION_DRIVER` |
| `GLIDE_DEFAULT_FORMAT` | `GLIDER_DEFAULT_FORMAT` |
| `GLIDE_DEFAULT_QUALITY` | `GLIDER_DEFAULT_QUALITY` |

### 5. Rename Blade components

All four component tags dropped the `glide` spelling in favor of `glider`.
The `glide-*` attribute prefix used for Glide manipulation params (e.g.
`glide-w`, `glide-fit`) is **unchanged** — only the tag names themselves
were renamed.

| v3 tag | v4 tag |
|---|---|
| `<x-glide-img>` | `<x-glider-img>` |
| `<x-glide-img-responsive>` | `<x-glider-img-responsive>` |
| `<x-glide-bg>` | `<x-glider-bg>` |
| `<x-glide-bg-responsive>` | `<x-glider-bg-responsive>` |

```diff
- <x-glide-img src="photo.jpg" glide-w="400" glide-q="85" alt="Photo" />
+ <x-glider-img src="photo.jpg" glide-w="400" glide-q="85" alt="Photo" />
```

A quick way to find every usage across your Blade views:

```bash
grep -rl "x-glide-" resources/views
```

### 6. Rename the facade

```diff
- use Daikazu\LaravelGlider\Facades\Glide;
+ use Daikazu\LaravelGlider\Facades\Glider;

- $url = Glide::url('photo.jpg', ['w' => 400, 'q' => 85]);
+ $url = Glider::url('photo.jpg', ['w' => 400, 'q' => 85]);
```

If you registered the facade alias yourself (rather than relying on
Laravel's package auto-discovery), update the alias entry in
`config/app.php` from `Glide` to `Glider` as well.

### 7. Rename the route

The image-serving route name changed from `glide` to `glider`. If you call
`route('glide', ...)` anywhere in your own code (custom URL generation,
tests, etc.), update it to `route('glider', ...)`. Most applications never
reference this route name directly — the package's own URL generation
already uses the new name internally.

### 8. New image URL format

v4 replaces the fully base64-encoded URL format with a human-readable one:

```
v3:  /img/Y29pbnMvdGhlbWVzL21lbW9yaWFsL2hlcm8uanBn/eyJmbSI6IndlYnAiLCJxIjoiODUiLCJ3IjoiMzMzIn0.webp
v4:  /img/coins/themes/memorial/hero~Zm09d2VicCZxPTg1JnNlPWpwZyZ3PTMzMw.webp
```

The source path and filename stay visible; only the manipulation parameters
ride in a compact token after the `~`. The trailing extension is still the
output format.

**Why the change:**

- **Image SEO** — search engines weigh descriptive image URLs and
  filenames; base64 threw that signal away entirely.
- **Debuggability** — you can read a URL in devtools, logs, or a
  Lighthouse report and know exactly which image and (decoding one small
  token) which parameters it refers to. Cached files on disk get the same
  readable names.
- **~30% shorter URLs**, which compounds across `srcset` attributes.

**What you need to do:**

- **Nothing for URLs your app generates.** Components and
  `Glider::url()` emit the new format automatically — every page render
  after deploying v4 uses new URLs.
- **Clear your v3 cache.** The cache layout mirrors the URL format, so
  v3-era cache entries are unreachable dead weight. Run
  `php artisan glider:clear` after deploying (and re-run `glider:build`
  if you prebuild).
- **Externally persisted v3 URLs will 404.** URLs that escaped your
  templates — hotlinks, emails already sent, search-engine image indexes,
  third-party embeds — no longer resolve. For most sites this is
  self-healing (crawlers re-index the new URLs from your pages), but if
  specific legacy URLs matter to you, add your own redirect layer for
  them; v4 does not serve the old format.

### 9. Command renames and flags

The tag-migration command was renamed:

| v3 | v4 |
|---|---|
| `php artisan glider:convert-img-tags` | `php artisan glider:convert` |

Both `glider:build` and `glider:clear` accept a new `--static` flag that
targets the baked `public/{base_url}` static tier instead of the configured
cache — see the README's Deployment Recipes for when to use it.

### 10. `ntzrbtr/flysystem-http` — no action needed

The `ntzrbtr/flysystem-http` dependency has been removed. Remote image URLs
(`<x-glider-img src="https://example.com/photo.jpg" ... />`) continue to
work exactly as before — v4 ships a first-party, read-only HTTP filesystem
adapter built on Laravel's own HTTP client. There is nothing to configure
or change; this is purely an internal dependency swap.

### 11. Review the new `strip` EXIF option (optional)

`league/glide` ^4.1 (via Intervention Image v4) adds a `strip` parameter
that removes EXIF/metadata (camera info, GPS coordinates, etc.) from
generated images. It is documented in the published config but **not
enabled by default** — existing behavior (metadata preserved) is
unchanged unless you opt in:

```php
// config/glider.php
'defaults' => [
    'fm' => env('GLIDER_DEFAULT_FORMAT', 'webp'),
    'q'  => env('GLIDER_DEFAULT_QUALITY', 85),
    'strip' => true, // uncomment to strip EXIF/metadata from all generated images
],
```

### 12. Optional new features

Nothing below is required to upgrade — all of it is opt-in and defaults to
v3-compatible behavior.

- **Disk-based source/cache/watermarks.** `source`, `cache`, and
  `watermarks` in `config/glider.php` now accept a Laravel disk reference
  (`['disk' => 's3', 'prefix' => 'glider']`) in addition to a plain path
  string. This enables an S3 shared cache (every app server reads from one
  pre-warmed cache) or baking a pre-built cache into your release artifact.
  See the README's "Disk-Based Cache" section for both recipes.
- **`php artisan glider:build`.** Scans your Blade templates for
  statically-resolvable `<x-glider-*>` / `Glider::url()` usages and
  prebuilds their cache entries ahead of time, so the first real request
  is already a cache hit. Run it in CI after your asset build step.
- **`on_the_fly` (default: `true`, unchanged).** Set
  `GLIDER_ON_THE_FLY=false` to disable on-the-fly image generation
  entirely once you're warming the cache via `glider:build` — any request
  for an uncached conversion then 404s instead of processing the image.
- **`restrict_to_presets` (default: `false`, unchanged).** Set
  `GLIDER_RESTRICT_TO_PRESETS=true` to reject (403) any request whose
  parameters don't exactly match a configured preset (format-only
  conversions via the URL extension are still allowed).

### 13. Re-run your test suite

Because component tags, the facade, config keys, and env vars all changed
names, search-and-replace is the main risk area. After updating, grep your
codebase one more time for leftover `glide-img`, `glide-bg`, `Glide::`,
`laravel-glider.`, and `GLIDE_` references (excluding the unchanged
`glide-*` attribute prefix) before shipping.

```bash
grep -rn "x-glide-\|Glide::\|laravel-glider\.\|GLIDE_" --include="*.php" --include="*.blade.php" .
```
