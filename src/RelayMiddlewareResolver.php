<?php

declare(strict_types=1);

namespace Nanofraim;

use Psr\Http\Server\MiddlewareInterface;

class RelayMiddlewareResolver
{
    public function resolveMiddleware($class, $serviceContainer): MiddlewareInterface
    {
        $instance = $serviceContainer->get($class);

        $serviceContainer->fulfillAwarenessTraits(
            $instance,
            [
                'Nanofraim\Trait\ServiceContainerAwareTrait' => [
                    'setServiceContainer' => fn () => $serviceContainer,
                ],
                'Psr\Log\LoggerAwareTrait' => [
                    'setLogger' => LoggerInterface::class,
                ],
                'Nanofraim\Trait\CacheAwareTrait' => [
                    'setCache' => CacheInterface::class,
                ],
                'Nanofraim\Trait\SessionAwareTrait' => [
                    'setSession' => Session::class,
                ],
            ]
        );

        return $instance;
    }
}
