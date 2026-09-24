## Laravel Glider

This app uses `daikazu/laravel-glider` for on-the-fly image manipulation (League/Glide) through Blade components and the `Glider` facade.

- Render images with the `<x-glider-img>`, `<x-glider-img-responsive>`, `<x-glider-bg>`, and `<x-glider-bg-responsive>` components rather than hand-built `<img>` tags or URLs.
- Pass Glide parameters as `glide-*` attributes (`glide-w="400"`, `glide-fit="crop"`, `glide-preset="thumbnail"`). Unprefixed attributes are passed through as HTML attributes.
- `src` is relative to the configured `glider.source` root (default `resources/assets`), not the public directory. Remote `http(s)://` URLs also work.
- Generate URLs in PHP with `Glider::url('photo.jpg', ['w' => 400])` (`Daikazu\LaravelGlider\Facades\Glider`). Never build `/img/...` URLs by hand: they carry an encoded token and an HMAC signature.
- Reuse the presets in `config/glider.php` (`presets`, `background_presets`) before adding one-off dimensions.
- Keep `GLIDER_SECURE=true` (signed URLs) outside local development.
- Activate the `glider-development` skill for component attributes, presets, `glider:build`/`glider:clear`/`glider:convert`, disk-based caches, and deployment recipes.
