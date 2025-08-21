{{-- Generate responsive background CSS --}}
{!! $generateBackgroundCSS() !!}

<div {{ $attributes->merge([
    'class' => $getCSSClass(),
    'data-glide-bg' => true,
    'data-glide-src' => $src
]) }}
@if ($getFallbackUrl())
    style="background-image: url('{{ $getFallbackUrl() }}'); background-position: {{ $position }}; background-size: {{ $size }}; background-repeat: {{ $repeat }}; background-attachment: {{ $attachment }};"
@endif
@if ($lazy)
    @foreach ($getLazyAttributes() as $attr => $value)
        {{ $attr }}="{{ $value }}"
    @endforeach
@endif
>
    {{ $slot }}
</div>