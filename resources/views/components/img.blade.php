<img
    src="{{ $src() }}"
    @if ($width() && $height())
        width="{{ $width() }}"
        height="{{ $height() }}"
    @endif
    {{ $attributes->whereDoesntStartWith('glide-')->merge([]) }}
>
