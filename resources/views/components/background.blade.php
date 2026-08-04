<div {{ $attributes->except(['focal-point'])->whereDoesntStartWith('glide-')->merge(array_merge([
    'style' => $backgroundStyle(),
    'data-glider-bg' => 'true',
    'data-glider-src' => $src,
], $getLazyAttributes())) }}>
    {{ $slot }}
</div>
