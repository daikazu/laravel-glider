<img
    src="{{ $src() }}"
    srcset="{{ $srcset() }}"
    @if ($width() && $height())
        width="{{ $width() }}"
        height="{{ $height() }}"
    @endif
    {{ $attributes->whereDoesntStartWith('glide-')->merge([]) }}
    onload="const vw=(document.documentElement.clientWidth||window.innerWidth);if(!vw)return;const w=this.getBoundingClientRect().width;if(!w)return;this.sizes=Math.max(1,Math.min(100,Math.round(w/vw*100)))+'vw';this.onload=null;"
>
