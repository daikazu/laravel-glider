@if($responsive)
<image srcset="
@if($attributes->get('width') >= '320'){{ Glider::url($src.'?p=xs') }} 320w @endif
@if($attributes->get('width') >= '480'), {{ Glider::url($src.'?p=sm') }} 480w @endif
@if($attributes->get('width') >= '768'), {{ Glider::url($src.'?p=md') }} 768w @endif
@if($attributes->get('width') >= '1280'), {{ Glider::url($src.'?p=lg') }} 1280w @endif
@if($attributes->get('width') >= '1440'), {{ Glider::url($src.'?p=xl') }} 1440w @endif
@if($attributes->get('width') >= '1680'), {{ Glider::url($src.'?p=2xl') }} 1680w @endif
"
sizes="1px"
onload="window.requestAnimationFrame(function(){if(!(size=getBoundingClientRect().width))return;onload=null;sizes=Math.ceil(size/window.innerWidth*100)+'vw';});"
@if($attributes->has('width') and $attributes->has('height'))
src="{{ Glider::url($src.'?w='.$attributes->get('width').'&h='.$attributes->get('height').'&fit=crop')}}"
@else
src="{{ Glider::url($src.'?p=lg')}}"
@endif
{{ $attributes }}>
@else
<img src="{{ Glider::url($src) }}" {{ $attributes }}>
@endif
