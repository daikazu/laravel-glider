{{-- Generate background CSS (already sanitized in method) --}}
{!! $generateBackgroundCSS() !!}

<div {{ $attributes->except(['focal-point'])->merge(array_merge([
    'class' => $getCSSClass(),
    'data-glide-bg' => true,
    'data-glide-src' => e($src)
], $getLazyAttributes())) }}
@if ($getFallbackUrl())
    style="background-image: url('{{ addcslashes($getFallbackUrl(), "'\\") }}'); background-position: {{ e($getBackgroundPosition()) }}; background-size: {{ e($size) }}; background-repeat: {{ e($repeat) }}; background-attachment: {{ e($attachment) }};"
@endif
>
    {{ $slot }}
</div>
