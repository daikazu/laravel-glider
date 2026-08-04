<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Support;

use Illuminate\Contracts\Container\Container;
use League\Glide\Server;

final readonly class ParamResolver
{
    public function __construct(private Container $app) {}

    public function normalize(array $params): array
    {
        if ($this->app->bound(Server::class)) {
            $params = $this->app->make(Server::class)->getAllParams($params);
        }

        unset($params['s'], $params['p']);
        $params = array_map(strval(...), $params);
        ksort($params);

        return $params;
    }

    public function mapPresetAlias(array $params): array
    {
        if (isset($params['preset'])) {
            $params['p'] = $params['preset'];
            unset($params['preset']);
        }

        return $params;
    }

    /**
     * Resolve the output extension and the final URL-token params in ONE
     * getAllParams pass. The single pass matters: an earlier two-pass
     * implementation stripped a redundant fm and then re-normalized, which
     * re-merged the config default fm over the user's explicit choice —
     * so glide-fm="png" served webp.
     *
     * fm is dropped from the token only when the controller's
     * `$params['fm'] ??= $extension` restoration reproduces it exactly
     * (which also means pjpg stays in the token, keeping progressive jpeg
     * intact through the round-trip).
     *
     * @return array{extension: string, params: array}
     */
    public function routeParams(string $path, array $params): array
    {
        $pathForExt = (string) parse_url($path, PHP_URL_PATH);
        $ext = strtolower(in_array(pathinfo($pathForExt, PATHINFO_EXTENSION), ['', '0'], true) ? '' : pathinfo($pathForExt, PATHINFO_EXTENSION));

        $resolved = $this->normalize($params);

        $format = $resolved['fm'] ?? ($ext !== '' ? $ext : null);
        $extension = $format === 'pjpg' ? 'jpg' : ($format ?? 'jpg');

        if (($resolved['fm'] ?? null) === $extension) {
            unset($resolved['fm']);
        }

        return [
            'extension' => $extension,
            'params'    => $resolved,
        ];
    }
}
