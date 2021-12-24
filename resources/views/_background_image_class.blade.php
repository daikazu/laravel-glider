<style>
    .{{$class_name}} {
        background-image: url("{!!  Glider::url($src.'?p=md') !!}");
        background-position: center;
    }

    @media only screen and (min-width: 320px) {
        .{{ $class_name }}  {
            background-image: url("{!! Glider::url($src.'?p=sm') !!}");
        }
    }

    @media only screen and (min-width: 480px), only screen and (min-width: 320px) and (-webkit-min-device-pixel-ratio: 2) {
        .{{ $class_name }}  {
            background-image: url("{!! Glider::url($src.'?p=md') !!}");
        }
    }

    @media only screen and (min-width: 768px), only screen and (min-width: 480px) and (-webkit-min-device-pixel-ratio: 2) {
        .{{ $class_name }}  {
            background-image: url("{!! Glider::url($src.'?p=lg') !!}");
        }
    }

    @media only screen and (min-width: 1280px), only screen and (min-width: 768px) and (-webkit-min-device-pixel-ratio: 2) {
        .{{ $class_name }}  {
            background-image: url("{!! Glider::url($src.'?p=xl') !!}");
        }
    }

    @media only screen and (min-width: 1440px), only screen and (min-width: 1280px) and (-webkit-min-device-pixel-ratio: 2) {
        .{{ $class_name }}  {
            background-image: url("{!! Glider::url($src.'?p=2xl') !!}");
        }
    }
</style>
