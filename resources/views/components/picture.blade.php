@once
    <script>
        window.responsiveResizeObserver = new ResizeObserver((entries) => {
            entries.forEach(entry => {
                const imgWidth = entry.target.getBoundingClientRect().width;
                entry.target.parentNode.querySelectorAll('source').forEach((source) => {
                    source.sizes = Math.ceil(imgWidth / window.innerWidth * 100) + 'vw';
                });
            });
        });
    </script>
@endonce

<picture>
    <source srcset="
{{ Glider::url($src.'?p=xs-webp') }} 320w,
{{ Glider::url($src.'?p=sm-webp') }} 480w,
{{ Glider::url($src.'?p=md-webp') }} 768w,
{{ Glider::url($src.'?p=lg-webp') }} 1280w,
{{ Glider::url($src.'?p=xl-webp') }} 1440w,
{{ Glider::url($src.'?p=2xl-webp') }} 1680w"
            sizes="1px"
            type="image/webp"
    >
    <source srcset="
{{ Glider::url($src.'?p=xs') }} 320w,
{{ Glider::url($src.'?p=sm') }} 480w,
{{ Glider::url($src.'?p=md') }} 768w,
{{ Glider::url($src.'?p=lg') }} 1280w,
{{ Glider::url($src.'?p=xl') }} 1440w,
{{ Glider::url($src.'?p=2xl') }} 1680w"
            sizes="1px"
            type="{{ $mime_type }}"
    >
    <img src="{{ Glider::url($src.'?p=lg') }}"
         onload="this.onload=null;window.responsiveResizeObserver.observe(this);"
        {{ $attributes }}>
</picture>