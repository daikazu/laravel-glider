<div {{ $attributes->except(['focus'])->whereDoesntStartWith('glide-')->merge(array_merge([
    'style' => $backgroundStyle(),
    'data-glider-bg' => 'true',
    'data-glider-src' => $src,
], $getLazyAttributes())) }}>
    {{ $slot }}
</div>
