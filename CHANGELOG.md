# Changelog

All notable changes to `laravel-glider` will be documented in this file.

## v4.0.1 - 2026-09-11

### Fixed

- Allow `guzzlehttp/guzzle` `^7.8 || ^8.0` so the package installs on fresh Laravel 13 apps that lock Guzzle 8 (#25). The tagged v4.0.0 release only permitted Guzzle 7.
- CI now also runs the test matrix on Windows.

## v4.0.0 - 2026-08-04

See [UPGRADE.md](UPGRADE.md) for a full v3 → v4 migration guide.

### ⚠️ Breaking Changes

- **Requirements raised**: now requires PHP ^8.3, Laravel 13 only (`illuminate/contracts` ^13.0; Laravel 11 and 12 are no longer supported), and `league/glide` ^4.1 (pulls in Intervention Image v4).
- **Human-readable image URLs**: the fully base64-encoded URL format (`/img/{b64 path}/{b64 params}.ext`) is replaced by `/img/{dirs...}/{name}~{token}.{ext}` — the source path and filename stay visible (image SEO, debuggability), URLs are ~30% shorter, and cached files get readable names on disk. Previously issued v3 URLs no longer resolve; clear your cache after upgrading and see UPGRADE.md for external-URL guidance.
- **Config file renamed**: `config/laravel-glider.php` → `config/glider.php`; every key moved from `laravel-glider.*` to `glider.*`. Re-publish with `php artisan vendor:publish --tag="glider-config"`.
- **Environment variables renamed**: every `GLIDE_*` variable is now `GLIDER_*` (full mapping in UPGRADE.md).
- **Blade components renamed**: `<x-glide-img>` → `<x-glider-img>`, `<x-glide-img-responsive>` → `<x-glider-img-responsive>`, `<x-glide-bg>` → `<x-glider-bg>`, `<x-glide-bg-responsive>` → `<x-glider-bg-responsive>`. The `glide-*` attribute prefix for manipulation params (e.g. `glide-w`) is unchanged.
- **`focal-point` attribute renamed to `focus`** on all four components.
- **Background component DOM output changed**: `<x-glider-bg>` renders an inline `style` on its container instead of a `<style>` block and generated class; `<x-glider-bg-responsive>`'s generated class prefix changed `.glide-bg-*` → `.glider-bg-*`; container data attributes renamed `data-glide-bg`/`data-glide-src` → `data-glider-bg="true"`/`data-glider-src`. The lazy-loading contract (`data-bg-lazy`/`data-bg-src`/`data-bg-srcset`) is unchanged.
- **Facade renamed**: `Daikazu\LaravelGlider\Facades\Glide` → `Daikazu\LaravelGlider\Facades\Glider`.
- **Route name renamed**: `glide` → `glider`.
- **Command renamed**: `glider:convert-img-tags` → `glider:convert`.
- **`ntzrbtr/flysystem-http` removed** as a dependency. Remote URL sources continue to work — v4 ships a first-party, read-only HTTP filesystem adapter built on Laravel's HTTP client. No user action required.
- Internals dissolved `GlideService`/`BaseComponent` into a composition-based architecture (`Glider` root class plus `Support/*`, `Security/*`, and `Build/*` collaborators). No public API impact beyond the renames above.

### ✨ New Features

- **`glider:build` command**: scans Blade templates (configurable via `glider.build.paths`) for statically-resolvable `<x-glider-*>` / `Glider::url()` usages and prebuilds their cache entries — byte-identical to what live requests generate — with live progress output. Supports `--dry-run` and `--static` (bake into `public/{base_url}` for web-server static serving). Dynamic `src` usages are reported and stay on-the-fly.
- **Disk-based storage**: `source`, `cache`, and `watermarks` accept a Laravel disk reference (`['disk' => 's3', 'prefix' => 'glider']`) or a path; env-expressible via `GLIDER_*_DISK`/`GLIDER_*_PREFIX`, and relative env paths resolve from the application root. Three documented deployment recipes: single server, shared cloud cache (Laravel Cloud/ephemeral infra), and hybrid baked-static + shared-dynamic.
- **Layered security**: signed URLs (default on) plus two new opt-in layers — `restrict_to_presets` (`GLIDER_RESTRICT_TO_PRESETS`, 403 for non-preset params) and an `on_the_fly` kill switch (`GLIDER_ON_THE_FLY`, cache misses 404 instead of processing). Signature verification is now a single request-time gate.
- **`sizes` prop on `<x-glider-img-responsive>`**: explicit `sizes` renders directly; lazy-loaded images default to `sizes="auto"`; the onload back-fill script remains only for the eager, no-sizes case. srcset `q`/`fm` are now defaults the user's `glide-q`/`glide-fm` override, and an explicit `fm` is no longer clobbered by the config default format.
- **`glider:clear` improvements**: handles disk-based caches, accepts `--static` to clear the baked tier independently, and sweeps empty per-image group folders.
- **`glider:convert` rewritten**: only converts statically-resolvable `src` attributes (plain paths and literal `asset()` calls); dynamic sources are left untouched; attributes survive with order, names, and quoting intact; `--image-path` prefix matching is slash-normalized.
- **`artisan about` section**: version, driver, base URL, source/cache locations, and the three security-layer states.
- **`strip` EXIF parameter** (via League/Glide 4.1): documented in the published config, disabled by default.
- Consistent error mapping across the request pipeline: invalid parameters → 400, preset-policy violations → 403, missing/unfetchable images → 404 (never 500).

## Unreleased (v4.0.0)

See [UPGRADE.md](UPGRADE.md) for a full v3 → v4 migration guide.

### ⚠️ Breaking Changes

- **Requirements raised**: now requires PHP ^8.3, Laravel 13 only (`illuminate/contracts` ^13.0; Laravel 11 and 12 are no longer supported), and `league/glide` ^4.1 (pulls in Intervention Image v4).
- **Human-readable image URLs**: the fully base64-encoded URL format (`/img/{b64 path}/{b64 params}.ext`) is replaced by `/img/{dirs...}/{name}~{token}.{ext}` — the source path and filename stay visible (image SEO, debuggability), URLs are ~30% shorter, and cached files get readable names on disk. Previously issued v3 URLs no longer resolve; clear your cache after upgrading and see UPGRADE.md for external-URL guidance.
- **Config file renamed**: `config/laravel-glider.php` → `config/glider.php`; every key moved from `laravel-glider.*` to `glider.*`. Re-publish with `php artisan vendor:publish --tag="glider-config"`.
- **Environment variables renamed**: every `GLIDE_*` variable is now `GLIDER_*` (full mapping in UPGRADE.md).
- **Blade components renamed**: `<x-glide-img>` → `<x-glider-img>`, `<x-glide-img-responsive>` → `<x-glider-img-responsive>`, `<x-glide-bg>` → `<x-glider-bg>`, `<x-glide-bg-responsive>` → `<x-glider-bg-responsive>`. The `glide-*` attribute prefix for manipulation params (e.g. `glide-w`) is unchanged.
- **`focal-point` attribute renamed to `focus`** on all four components.
- **Background component DOM output changed**: `<x-glider-bg>` renders an inline `style` on its container instead of a `<style>` block and generated class; `<x-glider-bg-responsive>`'s generated class prefix changed `.glide-bg-*` → `.glider-bg-*`; container data attributes renamed `data-glide-bg`/`data-glide-src` → `data-glider-bg="true"`/`data-glider-src`. The lazy-loading contract (`data-bg-lazy`/`data-bg-src`/`data-bg-srcset`) is unchanged.
- **Facade renamed**: `Daikazu\LaravelGlider\Facades\Glide` → `Daikazu\LaravelGlider\Facades\Glider`.
- **Route name renamed**: `glide` → `glider`.
- **Command renamed**: `glider:convert-img-tags` → `glider:convert`.
- **`ntzrbtr/flysystem-http` removed** as a dependency. Remote URL sources continue to work — v4 ships a first-party, read-only HTTP filesystem adapter built on Laravel's HTTP client. No user action required.
- Internals dissolved `GlideService`/`BaseComponent` into a composition-based architecture (`Glider` root class plus `Support/*`, `Security/*`, and `Build/*` collaborators). No public API impact beyond the renames above.

### ✨ New Features

- **`glider:build` command**: scans Blade templates (configurable via `glider.build.paths`) for statically-resolvable `<x-glider-*>` / `Glider::url()` usages and prebuilds their cache entries — byte-identical to what live requests generate — with live progress output. Supports `--dry-run` and `--static` (bake into `public/{base_url}` for web-server static serving). Dynamic `src` usages are reported and stay on-the-fly.
- **Disk-based storage**: `source`, `cache`, and `watermarks` accept a Laravel disk reference (`['disk' => 's3', 'prefix' => 'glider']`) or a path; env-expressible via `GLIDER_*_DISK`/`GLIDER_*_PREFIX`, and relative env paths resolve from the application root. Three documented deployment recipes: single server, shared cloud cache (Laravel Cloud/ephemeral infra), and hybrid baked-static + shared-dynamic.
- **Layered security**: signed URLs (default on) plus two new opt-in layers — `restrict_to_presets` (`GLIDER_RESTRICT_TO_PRESETS`, 403 for non-preset params) and an `on_the_fly` kill switch (`GLIDER_ON_THE_FLY`, cache misses 404 instead of processing). Signature verification is now a single request-time gate.
- **`sizes` prop on `<x-glider-img-responsive>`**: explicit `sizes` renders directly; lazy-loaded images default to `sizes="auto"`; the onload back-fill script remains only for the eager, no-sizes case. srcset `q`/`fm` are now defaults the user's `glide-q`/`glide-fm` override, and an explicit `fm` is no longer clobbered by the config default format.
- **`glider:clear` improvements**: handles disk-based caches, accepts `--static` to clear the baked tier independently, and sweeps empty per-image group folders.
- **`glider:convert` rewritten**: only converts statically-resolvable `src` attributes (plain paths and literal `asset()` calls); dynamic sources are left untouched; attributes survive with order, names, and quoting intact; `--image-path` prefix matching is slash-normalized.
- **`artisan about` section**: version, driver, base URL, source/cache locations, and the three security-layer states.
- **`strip` EXIF parameter** (via League/Glide 4.1): documented in the published config, disabled by default.
- Consistent error mapping across the request pipeline: invalid parameters → 400, preset-policy violations → 403, missing/unfetchable images → 404 (never 500).

## v3.3.1 - 2026-03-24

- Add Laravel 13 to CI test matrix
- Fix PHPStan error in ClearGlideCacheCommand
- Fix path traversal test failing in CI

## v3.3.0 - 2026-03-17

Added Laravel 13 Support

## v3.2.1 - 2025-12-10

### What's Changed

* chore(deps): bump actions/checkout from 5 to 6 by @dependabot[bot] in https://github.com/daikazu/laravel-glider/pull/9
* Dev by @daikazu in https://github.com/daikazu/laravel-glider/pull/11

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.2.0...v3.2.1

## v3.2.0 - Security Hardening & Component Fixes - 2025-11-07

🔒 Security Enhancements

Comprehensive security protections now enabled by default:

- SSRF Protection - Blocks malicious remote URLs targeting internal networks, cloud metadata endpoints, and
  private IP ranges
- Path Traversal Protection - Prevents directory traversal attacks, null byte injection, and symlink exploits
- XSS Protection - Sanitizes CSS values in background components to prevent injection attacks

All protections are automatic and require no configuration.

⚠️ Breaking Change

URL signing now defaults to true (previously false) to prevent DoS attacks. For local development, add
GLIDE_SECURE=false to your .env file if needed. Never disable in production.

✨ New Features

- Added missing `<x-glide-bg>` component for non-responsive background images

🐛 Bug Fixes

- Fixed inconsistent vendor:publish tag naming (now consistently prefixed with glider-)
- Fixed component view namespace resolution

📚 Documentation

- Added comprehensive Security section to README
- Refactored README for better readability
- Added security best practices and examples

🧪 Tests

- Added 50+ security tests (Path Traversal, XSS, SSRF)

🔄 Upgrade

composer update daikazu/laravel-glider

## v3.1.0 - 2025-10-05

### Features

- **Remote Image Support**: Images from external URLs are now properly processed, cached, and optimized #
  
  - Remote images automatically apply config defaults (format, quality, etc.)
  - Processed images are cached locally for better performance
  - All Glide manipulations work seamlessly with remote sources
  
- Added `Glider::url()` alias for `Glider::getUrl()`
  

### Bug Fixes

- Resolved PHPStan level 5 analysis errors with `parse_url()` type checks
- Fixed test compatibility between Orchestra Testbench v9 and v10

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.5...v3.1.0

## v3.0.4 - 2025-10-04

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.3...v3.0.4

## v3.0.3 - 2025-10-04

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.2...v3.0.3

## v3.0.2 - 2025-09-29

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.1...v3.0.2

## v3.0.1 - 2025-08-21

### What's Changed

* fixed storage:link filesystem location
* Bump actions/checkout from 4 to 5 by @dependabot[bot] in https://github.com/daikazu/laravel-glider/pull/6
* Bump stefanzweifel/git-auto-commit-action from 5 to 6 by @dependabot[bot] in https://github.com/daikazu/laravel-glider/pull/5

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/daikazu/laravel-glider/pull/6

**Full Changelog**: https://github.com/daikazu/laravel-glider/compare/v3.0.0...v3.0.1

## v3.0.0 - Major Release - 2025-08-20

🚀 Laravel Glider v3.0.0 - Major Release

Breaking Changes ⚠️

This is a major rewrite with breaking changes. Please review the README before upgrading.

- Minimum Requirements: Now requires PHP 8.3+ and Laravel 11+
- League/Glide v3: Updated to use the latest version of League/Glide
- Component Names: Some component naming has changed for consistency
- Configuration: New configuration structure with enhanced options

🎉 What's New

🖼️ Responsive Background Images

The marquee feature of v3! Introducing the powerful <x-glide-bg-responsive> component for responsive background images:

  <x-glide-bg-responsive src="hero.jpg" preset="hero" class="hero-section">
      <div class="hero-content">
          <h1>Welcome to Our Site</h1>
      </div>
  </x-glide-bg-responsive>
Features:
- Automatic CSS generation with media queries
- Preset system for consistent backgrounds
- Custom breakpoint support
- Lazy loading capabilities
- Fallback image support
🎨 Enhanced Blade Components
- New: <x-glide-bg-responsive> for responsive backgrounds
- Improved: Better attribute handling and performance
- Enhanced: Automatic srcset generation
⚙️ Advanced Configuration System
- Background Presets: Pre-configured responsive breakpoints for common use cases
- Enhanced Security: Improved URL signing and validation
- Better Defaults: WebP format and optimized quality settings by default
- Environment Variables: More configuration options via .env
📚 Comprehensive Documentation
- Complete API Reference: Full documentation of all components and methods
- Usage Examples: Extensive examples for all features
- Configuration Guide: Detailed configuration documentation
🛠️ Improvements
Performance
- Better caching strategies
- Optimized URL generation
- Reduced memory usage for large image sets

Developer Experience

- IDE Support: Full PhpDoc annotations and autocomplete
- Error Handling: Better error messages and validation
- Debugging: Enhanced debugging capabilities

Security

- Improved URL signing
- Better parameter validation
- Enhanced security defaults

📦 What's Included

- ✅ On-the-fly image processing
- ✅ Responsive background images (NEW!)
- ✅ Automatic srcset generation
- ✅ Security with signed URLs
- ✅ Performance optimizations
- ✅ Preset system for consistency
- ✅ Laravel 11+ compatibility
- ✅ PHP 8.3+ support
- ✅ Comprehensive documentation
