{{-- Generate responsive background CSS --}}
{!! $generateBackgroundCSS() !!}

<div {{ $attributes->merge(array_merge([
    'class' => $getCSSClass(),
    'data-glide-bg' => true,
    'data-glide-src' => $src
], $getLazyAttributes())) }}
@if ($getFallbackUrl())
    style="background-image: url('{{ $getFallbackUrl() }}'); background-position: {{ $position }}; background-size: {{ $size }}; background-repeat: {{ $repeat }}; background-attachment: {{ $attachment }};"
@endif
>
    {{ $slot }}
</div>
