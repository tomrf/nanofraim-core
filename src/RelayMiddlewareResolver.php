<?php

declare(strict_types=1);

namespace Nanofraim;

use Nanofraim\Exception\FrameworkException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Tomrf\ServiceContainer\ServiceContainer;
use Tomrf\Session\Session;

class RelayMiddlewareResolver
{
    public function resolveMiddleware(string $class, ServiceContainer $serviceContainer): MiddlewareInterface
    {
        $instance = $serviceContainer->get($class);

        if (!$instance instanceof MiddlewareInterface) {
            throw new FrameworkException('Middleware must implement MiddlewareInterface');
        }

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
