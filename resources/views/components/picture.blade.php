@props(['sizes' => '(min-width: 320px) 75vw, 90vw'])
<picture>
    <source srcset="
{{ Glider::url($src.'?p=xs-webp')  }} 320w,
{{ Glider::url($src.'?p=sm-webp')  }} 480w,
{{ Glider::url($src.'?p=md-webp')  }} 768w,
{{ Glider::url($src.'?p=lg-webp')  }} 1280w,
{{ Glider::url($src.'?p=xl-webp')  }} 1440w,
{{ Glider::url($src.'?p=2xl-webp')  }} 1680w"
    sizes="{{ $sizes }}"
    type="image/webp"
    >
    <source srcset="
{{ Glider::url($src.'?p=xs')  }} 320w,
{{ Glider::url($src.'?p=sm')  }} 480w,
{{ Glider::url($src.'?p=md')  }} 768w,
{{ Glider::url($src.'?p=lg')  }} 1280w,
{{ Glider::url($src.'?p=xl')  }} 1440w,
{{ Glider::url($src.'?p=2xl')  }} 1680w"
            sizes="{{ $sizes }}"
            type="{{ $mime_type }}"
    >
    <img src="{{ Glider::url($src.'?p=lg')  }}"
        {{ $attributes }}>
</picture>
