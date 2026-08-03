# Changelog

All notable changes to `laravel-glider` will be documented in this file.

## Unreleased (v4.0.0)

See [UPGRADE.md](UPGRADE.md) for a full v3 → v4 migration guide.

### ⚠️ Breaking Changes

- **Requirements raised**: now requires PHP ^8.3, Laravel 13 only (`illuminate/contracts` ^13.0; Laravel 11 and 12 are no longer supported), and `league/glide` ^4.1 (pulls in Intervention Image v4).
- **Config file renamed**: `config/laravel-glider.php` → `config/glider.php`. Every key moved from the `laravel-glider.*` namespace to `glider.*` (e.g. `config('laravel-glider.source')` → `config('glider.source')`). Re-publish with `php artisan vendor:publish --tag="glider-config"` and port over customizations.
- **Environment variables renamed**: every `GLIDE_*` variable is now `GLIDER_*` (`GLIDE_SECURE` → `GLIDER_SECURE`, `GLIDE_SOURCE_PATH` → `GLIDER_SOURCE_PATH`, `GLIDE_CACHE_PATH` → `GLIDER_CACHE_PATH`, `GLIDE_SIGN_KEY` → `GLIDER_SIGN_KEY`, `GLIDE_BASE_URL` → `GLIDER_BASE_URL`, `GLIDE_WATERMARKS_PATH` → `GLIDER_WATERMARKS_PATH`, `GLIDE_CACHE_WITH_EXTENSIONS` → `GLIDER_CACHE_WITH_EXTENSIONS`, `GLIDE_GROUP_CACHE` → `GLIDER_GROUP_CACHE`, `GLIDE_MAX_IMAGE_SIZE` → `GLIDER_MAX_IMAGE_SIZE`, `GLIDE_IMAGE_MANIPULATION_DRIVER` → `GLIDER_IMAGE_MANIPULATION_DRIVER`, `GLIDE_DEFAULT_FORMAT` → `GLIDER_DEFAULT_FORMAT`, `GLIDE_DEFAULT_QUALITY` → `GLIDER_DEFAULT_QUALITY`).
- **Blade components renamed**: `<x-glide-img>` → `<x-glider-img>`, `<x-glide-img-responsive>` → `<x-glider-img-responsive>`, `<x-glide-bg>` → `<x-glider-bg>`, `<x-glide-bg-responsive>` → `<x-glider-bg-responsive>`. The `glide-*` attribute prefix used for manipulation params (e.g. `glide-w`) is unchanged.
- **Facade renamed**: `Daikazu\LaravelGlider\Facades\Glide` → `Daikazu\LaravelGlider\Facades\Glider`.
- **Route name renamed**: the image-serving route `glide` → `glider`.
- **`ntzrbtr/flysystem-http` removed** as a dependency. Remote URL sources (`<x-glider-img src="https://...">`) continue to work — v4 ships a first-party, read-only HTTP filesystem adapter built on Laravel's HTTP client. No user action required.
- Internals dissolved `GlideService`/`BaseComponent` into a composition-based architecture (`Glider` root class plus `Support/*`, `Security/*`, and `Build/*` collaborators). No public API impact beyond the renames above.

### ✨ New Features

- **Disk-based storage**: `source`, `cache`, and `watermarks` in `config/glider.php` now accept a Laravel disk reference (`['disk' => 's3', 'prefix' => 'glider']`) in addition to a plain path string. Supports an S3 shared-cache recipe and a baked-into-artifact recipe.
- **`glider:build` command**: scans Blade templates (configurable via `glider.build.paths`, default `resources/views`) for statically-resolvable `<x-glider-*>` / `Glider::url()` usages and prebuilds their cache entries so the first live request is already a cache hit. Supports `--dry-run` and reports generated / skipped (dynamic src) / failed counts, exiting non-zero on any failure. Usages with a dynamic `src` stay on-the-fly.
- **`on_the_fly` security layer** (default: `true`, `GLIDER_ON_THE_FLY`): when disabled, cache misses return 404 instead of processing images on demand — pairs with `glider:build` in CI for zero request-time image processing in production.
- **`restrict_to_presets` security layer** (default: `false`, `GLIDER_RESTRICT_TO_PRESETS`): when enabled, only defaults-only requests, exact preset expansions, or whitelisted-extension format conversions are allowed; everything else returns 403.
- **`strip` EXIF parameter** (via League/Glide 4.1): documented in the published config to strip EXIF/metadata (e.g. GPS coordinates) from generated images. Left disabled by default — existing behavior is unchanged unless opted in.
- Consistent error mapping across the request pipeline: invalid parameters → 400, preset-policy violations → 403, missing/unfetchable images → 404.

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
