<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

final class PathCodec
{
    public function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    public function decode(string $encoded): ?string
    {
        $decoded = base64_decode($this->base64UrlToBase64($encoded), true);

        return $decoded === false ? null : $decoded;
    }

    public function encodeParams(array $params): string
    {
        $json = json_encode($params, JSON_UNESCAPED_SLASHES);

        return $this->encode($json ?: '{}');
    }

    public function decodeParams(string $encoded): array
    {
        $decoded = $this->decode($encoded);
        if ($decoded === null) {
            return [];
        }

        $data = json_decode($decoded, true);

        return is_array($data) ? $data : [];
    }

    private function base64UrlToBase64(string $input): string
    {
        $b64 = strtr($input, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad !== 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }

        return $b64;
    }
}
