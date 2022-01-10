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
@if($attributes->get('width') >= '320'){{ Glider::url($src.'?p=xs-webp') }} 320w @endif
@if($attributes->get('width') >= '480'), {{ Glider::url($src.'?p=sm-webp') }} 480w @endif
@if($attributes->get('width') >= '768'), {{ Glider::url($src.'?p=md-webp') }} 768w @endif
@if($attributes->get('width') >= '1280'), {{ Glider::url($src.'?p=lg-webp') }} 1280w @endif
@if($attributes->get('width') >= '1440'), {{ Glider::url($src.'?p=xl-webp') }} 1440w @endif
@if($attributes->get('width') >= '1680'), {{ Glider::url($src.'?p=2xl-webp') }} 1680w @endif
"
sizes="1px"
type="image/webp"
>
<source srcset="
@if($attributes->get('width') >= '320'){{ Glider::url($src.'?p=xs') }} 320w @endif
@if($attributes->get('width') >= '480'), {{ Glider::url($src.'?p=sm') }} 480w @endif
@if($attributes->get('width') >= '768'), {{ Glider::url($src.'?p=md') }} 768w @endif
@if($attributes->get('width') >= '1280'), {{ Glider::url($src.'?p=lg') }} 1280w @endif
@if($attributes->get('width') >= '1440'), {{ Glider::url($src.'?p=xl') }} 1440w @endif
@if($attributes->get('width') >= '1680'), {{ Glider::url($src.'?p=2xl') }} 1680w @endif
"
sizes="1px"
type="{{ $mime_type }}"
>
<img
@if($attributes->has('width') and $attributes->has('height'))
src="{{ Glider::url($src.'?w='.$attributes->get('width').'&h='.$attributes->get('height').'&fit=crop')}}"
@else
src="{{ Glider::url($src.'?p=lg')}}"
@endif"
onload="this.onload=null;window.responsiveResizeObserver.observe(this);"
{{ $attributes }}>
</picture>
