<picture>
   <source media="(prefers-color-scheme: dark)" srcset="art/header-dark.png">
   <img alt="Logo for Glider" src="art/header-light.png">
</picture>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-glider/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/laravel-glider/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/laravel-glider.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-glider)

# Laravel Glider

On-the-fly image manipulation for Laravel using [League/Glide](https://glide.thephpleague.com/). Transform, optimize, and serve images with elegant Blade components.

## Features

- **On-demand Processing** - Transform images via URL parameters
- **Readable URLs** - Source paths and filenames stay visible (`/img/coins/hero~token.webp`), good for debugging and image SEO
- **Responsive Images** - Automatic srcset and responsive backgrounds
- **Remote Images** - Process external URLs with caching
- **Secure URLs** - Signed URLs prevent unauthorized manipulation
- **Performance** - Built-in caching layer
- **Blade Components** - Clean syntax for templates
- **Presets** - Reusable image configurations
- **Disk-Based Storage** - Source, cache, and watermarks can live on any Laravel filesystem disk (e.g. S3)
- **Cache Prebuilding** - `glider:build` warms the cache from your Blade templates ahead of time
- **Layered Security** - Signed URLs, an on-the-fly kill switch, and presets-only mode, independently stackable

## Installation

```bash
composer require daikazu/laravel-glider
```

Publish configuration:

```bash
php artisan vendor:publish --tag="glider-config"
```

The cache directory is created automatically with `.gitignore` added.


## Quick Start

### Basic Image

```html
<x-glider-img
    src="photo.jpg"
    glide-w="400"
    glide-q="85"
    alt="Photo"
/>
```

### Responsive Image

```html
<x-glider-img-responsive
    src="hero.jpg"
    glide-w="1200"
    alt="Hero image"
/>
```

### Background Image

```html
<x-glider-bg src="banner.jpg" glide-w="1440" glide-h="600" class="hero-section">
    <h1>Welcome</h1>
</x-glider-bg>
```

### Responsive Background

```html
<x-glider-bg-responsive
    src="banner.jpg"
    glide-w="1440"
    glide-h="600"
/>
    <h1>Content</h1>
</x-glider-bg-responsive>
```

### Using the Facade

```php
use Daikazu\LaravelGlider\Facades\Glider;

// Generate URL
$url = Glider::url('photo.jpg', ['w' => 400, 'q' => 85]);
```

> **Upgrading from v3?** Component tags (`x-glide-*` → `x-glider-*`), the
> facade (`Glide` → `Glider`), the config file (`config/laravel-glider.php` →
> `config/glider.php`), and environment variables (`GLIDE_*` → `GLIDER_*`)
> were all renamed in v4. See [UPGRADE.md](UPGRADE.md) for the full mapping
> and step-by-step instructions.

## Usage Guide

### Image Manipulation

All [Glide parameters](https://glide.thephpleague.com/4.0/api/quick-reference/) are supported with the `glide-` prefix:

**Sizing**
```html
<x-glider-img src="photo.jpg" glide-w="400" glide-h="300" glide-fit="crop" />
```

**Quality & Format**
```html
<x-glider-img src="photo.jpg" glide-q="85" glide-fm="webp" />
```

**Effects**
```html
<x-glider-img src="photo.jpg" glide-blur="5" glide-filt="greyscale" />
```

### Focal Points

Control image positioning within its container using CSS:

```html
<!-- Named positions -->
<x-glider-img src="portrait.jpg" focus="top" glide-w="400" glide-h="300" />

<!-- Custom percentages (x, y) -->
<x-glider-img src="photo.jpg" focus="75,25" glide-w="400" glide-h="300" />

<!-- On backgrounds -->
<x-glider-bg src="hero.jpg" focus="center" preset="hero">
    <h1>Content</h1>
</x-glider-bg>
```

**Available positions:** `center`, `top`, `bottom`, `left`, `right`, `top-left`, `top-right`, `bottom-left`, `bottom-right`

**How it works:**
- For `<img>`: Applies `object-fit: cover` and `object-position`
- For backgrounds: Sets `background-position` in CSS
- For server-side cropping, use `glide-fit="crop-top"` or `glide-fit="crop-25-75"`

### Presets

Define reusable configurations in `config/glider.php`:

```php
'presets' => [
    'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
    'hero' => ['w' => 1200, 'h' => 600, 'fit' => 'crop', 'q' => 90],
],
```

Use in components:

```html
<x-glider-img src="photo.jpg" glide-preset="thumbnail" />
```

### Background Images

Create responsive backgrounds with automatic media queries:

```html
<x-glider-bg
    src="hero.jpg"
    preset="hero"
    position="center top"
    class="hero-section"
>
    <div class="content">
        <h1>Hero Title</h1>
    </div>
</x-glider-bg>
```

**Custom breakpoints:**

```html
<x-glider-bg
    src="banner.jpg"
    :breakpoints="[
        'xs' => ['w' => 768, 'h' => 300],
        'lg' => ['w' => 1440, 'h' => 500]
    ]"
>
    <div class="content">
        <h1>Hero Title</h1>
    </div>
</x-glider-bg>
```

**Background presets** in config:

```php
'background_presets' => [
    'hero' => [
        'xs' => ['w' => 768, 'h' => 400, 'fit' => 'crop'],
        'md' => ['w' => 1024, 'h' => 500, 'fit' => 'crop'],
        'lg' => ['w' => 1440, 'h' => 600, 'fit' => 'crop'],
    ],
],
```

### Remote Images

Process images from external URLs automatically:

```html
<x-glider-img
    src="https://example.com/photo.jpg"
    glide-w="600"
    glide-fm="webp"
    alt="Remote image"
/>
```

Remote images are fetched, processed, and cached locally. Default config values apply automatically. Fetching is handled by a first-party, read-only HTTP filesystem adapter built on Laravel's own HTTP client — no extra dependencies or configuration required.

## Components Reference

### `<x-glider-img>`

Basic image with transformations.

**Attributes:**
- `src` - Image path (required)
- `focus` - CSS positioning (e.g., `top`, `75,25`)
- `glide-*` - Any Glide parameter (see [Parameters](#glide-parameters))
- Standard HTML `<img>` attributes (alt, class, loading, etc.)

### `<x-glider-img-responsive>`

Responsive image with automatic srcset generation.

**Attributes:**
- Same as `<x-glider-img>`
- `srcset-widths` - Comma-separated list of widths to generate (e.g.
  `"400,800,1200"`); when omitted, widths are calculated automatically
  from the source image's dimensions and file size
- `sizes` - The standard `sizes` attribute (e.g.
  `"(min-width: 768px) 50vw, 100vw"`), so the browser picks the right
  srcset candidate on the very first fetch. When omitted: lazy-loaded
  images (`loading="lazy"`) get `sizes="auto"`, and eager images fall back
  to a small `onload` script that back-fills `sizes` after first paint
- Generates multiple sizes for different viewports

### `<x-glider-bg>`

Background image container (non-responsive). For breakpoint-based
`background_presets`, use `<x-glider-bg-responsive>` — this component
renders a single background image (use `glide-preset` for regular
manipulation presets).

**Attributes:**
- `src` - Image path (required)
- `focus` - CSS positioning
- `position` - CSS background-position (default: `center`)
- `size` - CSS background-size (default: `cover`)
- `repeat` - CSS background-repeat (default: `no-repeat`)
- `attachment` - CSS background-attachment (default: `scroll`)
- `lazy` - Emit `data-bg-lazy`/`data-bg-src` attributes for your lazy loader
- `fallback` - Fallback image path (rendered as an inline style)
- `class` - CSS classes for container
- `glide-*` - Any Glide parameter

### `<x-glider-bg-responsive>`

Responsive background with media queries.

**Attributes:**
- `src` - Image path (required)
- `preset` - Background preset name (from `background_presets` config)
- `breakpoints` - Custom breakpoint array
- `focus` - CSS positioning for all breakpoints
- `position` / `size` / `repeat` / `attachment` - CSS background properties
  (same defaults as `<x-glider-bg>`)
- `lazy` - Emit `data-bg-lazy`/`data-bg-src`/`data-bg-srcset` attributes
- `fallback` - Fallback image path (rendered as an inline style)
- `glide-*` - Any Glide parameter

## Glide Parameters

Common parameters (use `glide-` prefix in components):

| Parameter | Values | Description |
|-----------|--------|-------------|
| `w` | pixels | Width |
| `h` | pixels | Height |
| `fit` | `crop`, `contain`, `fill`, `max` | Resize mode |
| `fit` | `crop-{position}` | Crop with position (e.g., `crop-top`, `crop-center`) |
| `fit` | `crop-{x}-{y}[-{zoom}]` | Crop with focal point/zoom (e.g., `crop-25-75-2`) |
| `q` | 1-100 | Quality |
| `fm` | `jpg`, `png`, `webp`, `avif` | Format |
| `blur` | 0-100 | Blur amount |
| `bri` | -100 to 100 | Brightness |
| `con` | -100 to 100 | Contrast |
| `filt` | `greyscale`, `sepia` | Filter |

See [full Glide documentation](https://glide.thephpleague.com/) for all parameters.

## Artisan Commands

**Clear image cache:**
```bash
# Clear the configured cache — works for plain paths and disk references
# (e.g. an S3 cache) alike, and sweeps the empty per-image group folders
# left behind by `group_cache_in_folders`
php artisan glider:clear

# Clear the baked static tier (public/{base_url}, from `glider:build
# --static`) instead of the configured cache — the runtime cache is untouched
php artisan glider:clear --static

# Skip the confirmation prompt in production
php artisan glider:clear --force
```

Cached conversions are byte-identical whether they came from `glider:build`
or an on-the-fly request, so within a single cache store there is no
"prebuilt only" filter — but since the baked static tier and the runtime
cache are separate stores in the hybrid recipe, `--static` lets you clear
either one independently.

**Prebuild all statically discoverable image conversions:**
```bash
php artisan glider:build
```

**Convert HTML img tags to components:**
```bash
# Always preview first — this command rewrites your Blade files
php artisan glider:convert --dry-run

# Apply (asks for confirmation; --backup creates timestamped copies first)
php artisan glider:convert --backup
```

Only tags with a statically-resolvable `src` are converted — plain paths and
`asset('literal')` wrappers. Dynamic sources (`:src` bindings, Blade echoes,
`url()`/`Vite::asset()`/`Storage::url()` helpers, concatenated `asset()`
expressions) are left untouched. All other attributes survive with their
order, names, and quoting intact.

Use `--image-path` to declare which public-URL prefix maps to your glider
`source` root — that prefix is stripped from converted srcs (leading slashes
don't matter on either side):

```bash
# Default: /images/ URLs map to the source root (e.g. public/images
# symlinked to resources/assets) — asset('images/theme/logo.png')
# becomes src="theme/logo.png"
php artisan glider:convert --dry-run

# Your public URLs use a different prefix
php artisan glider:convert --dry-run --image-path=assets/

# Your source root actually contains the prefix folder — strip nothing
php artisan glider:convert --dry-run --image-path=
```

Always check the `--dry-run` preview: converted srcs must resolve relative
to your configured `glider.source`.

See [Prebuilding the Cache](#prebuilding-the-cache-gliderbuild) below for details on `glider:build`.

## Configuration

Key options in `config/glider.php`:

```php
return [
    'source' => resource_path('assets'),
    'cache' => storage_path('app/glider-cache'),
    'base_url' => 'img',
    'max_image_size' => 2000 * 2000,
    'driver' => 'gd', // or 'imagick'

    'defaults' => ['fm' => 'webp', 'q' => 85],

    'presets' => [
        'thumbnail' => ['w' => 150, 'h' => 150, 'fit' => 'crop'],
    ],

    'background_presets' => [
        'hero' => [
            'xs' => ['w' => 768, 'h' => 400],
            'lg' => ['w' => 1440, 'h' => 600],
        ],
    ],
];
```

`source`, `cache`, and `watermarks` each accept either a plain path string
or a Laravel disk reference (`['disk' => 's3', 'prefix' => 'glider']`) — see
[Disk-Based Cache](#disk-based-cache) below.

League/Glide 4.1 also adds a `strip` parameter (add `'strip' => true` to
`defaults` or a preset) to remove EXIF/metadata — such as GPS coordinates —
from generated images. It's documented in the published config but left
disabled by default.

## Prebuilding the Cache (`glider:build`)

`php artisan glider:build` scans your Blade templates (by default,
everything under `resources/views`, configurable via `glider.build.paths`)
for `<x-glider-img>`, `<x-glider-img-responsive>`, `<x-glider-bg>`,
`<x-glider-bg-responsive>`, and `Glider::url()` usages whose `src` is a
literal string, and generates their cache entries ahead of time — using the
exact same param resolution a live request would use. This means the first
real request for a prebuilt conversion is already a cache hit.

```bash
php artisan glider:build
```

```bash
# Preview what would be generated without writing anything to cache
php artisan glider:build --dry-run

# Bake conversions into public/{base_url} for static serving — used by the
# hybrid deployment recipe to build the static tier into the release
# artifact while the runtime cache lives elsewhere (see Deployment Recipes
# below). The target is derived from config, so it always aligns with the
# URLs the web server will receive.
php artisan glider:build --static
```

Usages with a dynamic `src` (a variable or expression rather than a literal
string) can't be discovered by scanning and are reported as **skipped**
rather than silently ignored — they'll still be processed on-the-fly at
request time. The command reports generated / skipped (dynamic src) /
failed counts, and exits non-zero if any conversion failed to generate.

**CI example** — run the build step after your asset build so the cache is
warm before the release ships:

```yaml
- name: Build assets
  run: npm run build

- name: Prebuild image cache
  run: php artisan glider:build
```

Pair this with `'on_the_fly' => false` (see [Security](#security) below) to
make production serve nothing but pre-warmed cache entries — zero
request-time image processing.

## Disk-Based Filesystems & Deployment Recipes

`source`, `cache`, and `watermarks` in `config/glider.php` each accept
either a plain path string or a Laravel disk reference:

```php
'cache' => ['disk' => 's3', 'prefix' => 'glider-cache'],
```

Since `.env` can't express arrays (or call path helpers), both forms are
also available as environment variables:

```dotenv
# Path form — relative paths resolve from the application root:
GLIDER_CACHE_PATH=public/img

# Disk form — takes precedence over the path form when set:
GLIDER_CACHE_DISK=s3
GLIDER_CACHE_PREFIX=glider-cache        # optional, defaults to "glider-cache"
```

(`GLIDER_SOURCE_DISK`/`GLIDER_SOURCE_PREFIX` and
`GLIDER_WATERMARKS_DISK`/`GLIDER_WATERMARKS_PREFIX` work the same way.)

> **Note:** a non-local `source` (any disk reference other than the local
> filesystem) disables automatic `width()`/`height()` attributes and
> automatic `srcset` width calculation, since both require reading the
> source image's dimensions from disk. An explicit `srcset-widths` list
> still works.

### The static-serve property

Glider's cache layout mirrors its URL structure. If the cache root is
`public/{base_url}` (default: `public/img`), a cached conversion is a real
file at exactly the path its URL requests — so **the web server serves it
as a static file and PHP is never invoked**. Anything *not* in the cache
falls through to the Laravel route as usual. This one property powers the
recipes below; no extra configuration is involved.

### Recipe 1: Single server (simplest)

A classic VPS/Forge box. Point the cache at `public/img`, keep on-the-fly
enabled, and optionally prebuild on deploy:

```dotenv
GLIDER_CACHE_PATH=public/img
GLIDER_SECURE=true
```

```bash
# deploy script (optional but recommended)
php artisan glider:build
```

Prebuilt conversions are static-served from day one; anything dynamic is
generated once on first request, lands in `public/img`, and is
static-served from then on. **Choose this when** you deploy to one or more
persistent servers with a durable local disk.

### Recipe 2: Shared cloud cache (ephemeral/autoscaling infrastructure)

Laravel Cloud, Vapor, Kubernetes — anywhere instances are ephemeral, local
writes don't survive, and replicas must share state:

```dotenv
GLIDER_CACHE_DISK=s3          # e.g. the auto-provisioned bucket on Laravel Cloud
GLIDER_ON_THE_FLY=true
GLIDER_SECURE=true
```

```bash
# deploy command
php artisan glider:build
```

Every instance — including fresh autoscale replicas — shares the same warm
bucket cache. Dynamic (CMS/database-driven) images are generated once
globally on first request, and the platform CDN caches everything after the
first serve (Glider sends `public, max-age=1yr` headers). **Choose this
when** everything is dynamic or you want the fewest moving parts on cloud
infrastructure.

### Recipe 3: Hybrid — baked static + shared dynamic (best of both)

For sites where most images are local/static assets but some come from a
CMS or database. Bake the static conversions **into the deployment
artifact** at build time with `--static`, while the runtime cache points
at the shared bucket:

```dotenv
# runtime environment
GLIDER_CACHE_DISK=s3
GLIDER_ON_THE_FLY=true
GLIDER_SECURE=true
```

```bash
# BUILD command (runs while the artifact is created, e.g. Laravel Cloud
# build step or CI before packaging). Bakes into public/{base_url} —
# derived from config, so it always aligns with the request URLs:
php artisan glider:build --static
```

How the split works — with zero routing configuration:

- **Static images** are baked into `public/img` inside the artifact →
  served as static assets by the web server/CDN edge. No PHP, no bucket,
  on every instance, immediately.
- **Dynamic images** have no baked file, so they fall through to the route
  → generated once into the shared bucket → CDN-cached after first serve.

The web server itself decides which tier serves each request, simply by
whether the file exists. **Choose this when** a meaningful share of your
images are static template assets and you want them served at static-file
speed even on ephemeral infrastructure.

### Which recipe?

| Your situation | Recipe |
|---|---|
| Persistent server(s), durable disk | **1** — local `public/img` cache |
| Ephemeral/autoscaling, mostly dynamic images | **2** — shared bucket |
| Ephemeral/autoscaling, mostly static images | **3** — baked + bucket |

All three use the same package configuration surface — they differ only in
env values and where `glider:build` runs.

## Requirements

- PHP 8.3+
- Laravel 13.x
- GD or Imagick extension
- League/Glide 4.1+

## Security

Laravel Glider implements multiple security layers to protect your application from common attacks. These features work together to ensure safe image processing.

### Three-Layer Security Model

Beyond automatic protections (path traversal, XSS, SSRF), Glider offers
three independent, stackable layers you can combine to control how much of
the parameter space is reachable at request time:

1. **URL Signing** (`secure`) — every URL must carry a valid HMAC
   signature, so only your application can mint valid image URLs. Primary
   defense; keep enabled in production.
2. **On-the-Fly Kill Switch** (`on_the_fly`) — once you've warmed the
   cache for the conversions you actually use (via `php artisan
   glider:build`), disable on-the-fly generation entirely. Requests for
   already-cached conversions are served normally; requests for anything
   uncached 404 instead of invoking the image processor.
3. **Presets-Only Mode** (`restrict_to_presets`) — restricts allowed
   manipulations to your configured presets (plus defaults-only and
   format-only requests), rejecting anything else with a 403.

These compose freely: signed URLs only, signed + presets-only, on-the-fly
disabled after a cache-warming step, or any combination.

### URL Signing

**Status:** Enabled by default (`GLIDER_SECURE=true`)

URL signing prevents unauthorized image manipulation and protects against denial-of-service attacks. When enabled, all image URLs are cryptographically signed using your application key.

```php
// config/glider.php
'secure' => env('GLIDER_SECURE', true),
```

**Important:** URL signing should **NEVER** be disabled in production environments. Unsigned URLs allow attackers to:
- Generate infinite variations of images, exhausting server resources
- Perform expensive image operations repeatedly
- Fill disk space with cached attack images

The signing mechanism uses Laravel's `APP_KEY` by default. Ensure your application key is:
- Generated with `php artisan key:generate`
- Kept secure and never committed to version control
- Properly configured in production environments

### On-the-Fly Kill Switch

**Status:** Enabled by default (`GLIDER_ON_THE_FLY=true`)

When `on_the_fly` is disabled, the server never runs the image processor at
request time — a request for a conversion that's already in cache is
served from cache; a request for anything not already cached returns a
404. This turns image processing into a build-time step rather than a
request-time one.

```php
// config/glider.php
'on_the_fly' => env('GLIDER_ON_THE_FLY', true),
```

Pair this with [`php artisan glider:build`](#prebuilding-the-cache-gliderbuild)
in your CI/deploy pipeline: warm the cache for every conversion your
templates statically reference, then disable `on_the_fly` so production
does zero request-time image processing. Any usage with a dynamic `src`
(not discoverable by the build scan) will 404 under this mode unless it
happens to already be cached from an earlier request.

### Presets-Only Mode

**Status:** Disabled by default (`GLIDER_RESTRICT_TO_PRESETS=false`)

When enabled, any request whose parameters don't exactly match a
configured preset's expansion (or a defaults-only / format-only request)
is rejected with a 403. This collapses the reachable parameter space down
to `(number of images) × (number of presets)`, closing off the
arbitrary-parameter attack surface even when signed URLs are otherwise
trusted.

```php
// config/glider.php
'restrict_to_presets' => env('GLIDER_RESTRICT_TO_PRESETS', false),
```

**Important:** this is not an absolute "presets only" guarantee — format
conversions via the URL's file extension (`.webp`, `.avif`, etc.) are
still permitted even when the request's other params don't match any
preset, because the route itself already whitelists which extensions are
accepted (`jpg`, `pjpg`, `png`, `gif`, `webp`, `avif`, `tiff`). The
practical guarantee is "images × presets × whitelisted extensions," not
"presets only."

### Path Traversal Protection

Laravel Glider validates all file paths to prevent directory traversal attacks. The package automatically:

- **Validates path boundaries** - Ensures all paths resolve within the configured source directory
- **Blocks null bytes** - Prevents null byte injection attacks (`../../../etc/passwd%00.jpg`)
- **Prevents symlink attacks** - Validates real paths to stop symlink-based directory escapes
- **Sanitizes input** - Removes dangerous characters from file paths

Example of blocked attacks:
```
❌ ../../../etc/passwd
❌ /var/www/../../etc/shadow
❌ image.jpg%00.php
❌ symlink-to-sensitive-dir/file.jpg
```

These protections are automatic and require no configuration.

### XSS Protection

Background image components sanitize CSS values to prevent cross-site scripting attacks via CSS injection:

```html
<!-- Safe: CSS values are sanitized -->
<x-glider-bg src="hero.jpg" position="center top" />

<!-- Protected: Malicious CSS is blocked -->
<x-glider-bg src="hero.jpg" position="center; background: url(javascript:alert('XSS'))" />
```

The package validates and sanitizes:
- `position` attributes
- `size` attributes
- `repeat` attributes
- `attachment` attributes
- `focus` values

### SSRF Protection

When processing remote images via URLs, Laravel Glider protects against Server-Side Request Forgery (SSRF) attacks:

```html
<!-- Safe: Public URLs are allowed -->
<x-glider-img src="https://cdn.example.com/image.jpg" glide-w="400" />

<!-- Blocked: Private/internal targets are prevented -->
❌ http://localhost/admin/secret.jpg
❌ http://127.0.0.1/internal/image.jpg
❌ http://192.168.1.1/router-config.jpg
❌ http://10.0.0.5/database-backup.jpg
❌ http://169.254.169.254/latest/meta-data (AWS metadata)
```

**Protections implemented:**

- **Blocks localhost access** - Prevents requests to `localhost`, `127.0.0.1`, `::1`, and `0.0.0.0`
- **Blocks private IP ranges** - Rejects RFC1918 private addresses (10.x.x.x, 172.16-31.x.x, 192.168.x.x)
- **Blocks link-local addresses** - Prevents access to 169.254.x.x range (cloud metadata endpoints)
- **Blocks dangerous ports** - Rejects connections to common internal service ports (SSH:22, MySQL:3306, Redis:6379, etc.)
- **Validates URL schemes** - Only allows `http://` and `https://` protocols
- **DNS resolution validation** - Resolves hostnames to IPs and validates against private ranges

These protections prevent attackers from:
- Scanning internal network infrastructure
- Accessing cloud provider metadata endpoints
- Reaching internal services and databases
- Port scanning internal systems
- Bypassing firewall rules via your server

All SSRF protections are automatic and require no configuration.

### Security Best Practices

Follow these recommendations to maintain secure image processing:

1. **Keep URL signing enabled**
   ```bash
   # .env (production)
   GLIDER_SECURE=true
   ```

2. **Use a strong application key**
   ```bash
   # Generate a secure key
   php artisan key:generate
   ```

3. **Validate source paths**
   ```php
   // Ensure images are within intended directories
   'source' => resource_path('assets/images'),
   ```

4. **Limit maximum image dimensions**
   ```php
   // config/glider.php
   'max_image_size' => 2000 * 2000, // Prevent memory exhaustion
   ```

5. **Keep the package updated**
   ```bash
   composer update daikazu/laravel-glider
   ```

6. **Use HTTPS in production**
    - Protects signed URLs from interception
    - Prevents man-in-the-middle attacks on image requests

7. **Configure appropriate cache permissions**
   ```bash
   # Ensure cache directory has proper permissions
   chmod 755 storage/app/glider-cache
   ```

8. **Consider `restrict_to_presets` for user-influenced sources**
   ```bash
   # Collapse the parameter space to images x presets x whitelisted extensions
   GLIDER_RESTRICT_TO_PRESETS=true
   ```

9. **Consider `on_the_fly=false` once your cache is warm**
   ```bash
   # Serve only pre-cached conversions generated by `glider:build`
   GLIDER_ON_THE_FLY=false
   ```

### Reporting Security Issues

If you discover a security vulnerability, please email [daikazu@gmail.com] or use the [GitHub Security Advisory](https://github.com/daikazu/laravel-glider/security) feature. Do not create public issues for security vulnerabilities.

## Testing

```bash
composer test          # Run tests
composer test-coverage # With coverage
composer analyse       # Static analysis
```

## Resources

- [Changelog](CHANGELOG.md)
- [League/Glide Documentation](https://glide.thephpleague.com/)
- [Report Security Issues](https://github.com/daikazu/laravel-glider/security)

## Credits

- [Mike Wall](https://github.com/daikazu)
- [All Contributors](https://github.com/daikazu/laravel-glider/contributors)

## License

MIT License. See [LICENSE.md](LICENSE.md) for details.
