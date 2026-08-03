<?php

declare(strict_types=1);

namespace Daikazu\LaravelGlider\Security;

use Daikazu\LaravelGlider\Support\ParamResolver;
use Illuminate\Contracts\Container\Container;
use League\Glide\Server;

/**
 * Enforces `glider.restrict_to_presets`: only defaults-only requests or
 * requests whose params exactly match a configured preset's expansion
 * are allowed.
 */
final class PresetPolicy
{
    public function __construct(
        private readonly ParamResolver $params,
        private readonly Container $app,
    ) {}

    public function allows(array $requestParams): bool
    {
        if (array_diff_key($requestParams, ['fm' => true, 's' => true]) === []) {
            return true;
        }

        $normalizedRequest = $this->params->normalize($requestParams);

        /** @var Server $server */
        $server = $this->app->make(Server::class);

        foreach (array_keys((array) config('glider.presets', [])) as $name) {
            $presetExpansion = $server->getAllParams(['p' => $name]);

            if ($this->params->normalize($presetExpansion) === $normalizedRequest) {
                return true;
            }
        }

        return false;
    }
}
