<img
    src="{{ $src() }}"
    srcset="{{ $srcset() }}"
    @if ($width() && $height())
        width="{{ $width() }}"
        height="{{ $height() }}"
    @endif
    @if ($objectPosition())
        style="object-fit: cover; object-position: {{ $objectPosition() }}; {{ $attributes->get('style') }}"
    @endif
    {{ $attributes->except(['focal-point', 'style'])->whereDoesntStartWith('glide-')->merge([]) }}
    onload="const vw=(document.documentElement.clientWidth||window.innerWidth);if(!vw)return;const w=this.getBoundingClientRect().width;if(!w)return;this.sizes=Math.max(1,Math.min(100,Math.round(w/vw*100)))+'vw';this.onload=null;"
>
