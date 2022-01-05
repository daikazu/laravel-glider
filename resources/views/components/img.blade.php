@if($responsive)
<image srcset="
{{ Glider::url($src.'?p=xs')  }} 320w,
{{ Glider::url($src.'?p=sm')  }} 480w,
{{ Glider::url($src.'?p=md')  }} 768w,
{{ Glider::url($src.'?p=lg')  }} 1280w,
{{ Glider::url($src.'?p=xl')  }} 1440w,
{{ Glider::url($src.'?p=2xl')  }} 1680w"
           sizes="1px"
           onload="window.requestAnimationFrame(function(){if(!(size=getBoundingClientRect().width))return;onload=null;sizes=Math.ceil(size/window.innerWidth*100)+'vw';});"
           src="{{ Glider::url($src.'?p=lg')}}"
    {{ $attributes }}>
@else
<img src="{{ Glider::url($src) }}" {{ $attributes }}>
@endif
