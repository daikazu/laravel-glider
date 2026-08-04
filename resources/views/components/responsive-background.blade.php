{{-- Generate responsive background CSS (already sanitized in method) --}}
{!! $generateBackgroundCSS() !!}

<div {{ $attributes->except(['focus'])->whereDoesntStartWith('glide-')->merge(array_merge([
    'class' => $getCSSClass(),
    'data-glider-bg' => 'true',
    'data-glider-src' => $src,
], $getLazyAttributes(), $getFallbackUrl() ? [
    'style' => "background-image: url('" . addcslashes($getFallbackUrl(), "'\\") . "');",
] : [])) }}>
    {{ $slot }}
</div>
