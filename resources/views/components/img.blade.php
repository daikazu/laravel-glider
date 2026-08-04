<img
    src="{{ $src() }}"
    @if ($width() && $height())
        width="{{ $width() }}"
        height="{{ $height() }}"
    @endif
    @if ($objectPosition())
        style="object-fit: cover; object-position: {{ $objectPosition() }}; {{ $attributes->get('style') }}"
    @endif
    {{ $attributes->except(['focus', 'style'])->whereDoesntStartWith('glide-')->merge([]) }}
>
