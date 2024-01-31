<?php

declare(strict_types=1);

namespace Nanofraim;

use Nanofraim\Exception\FrameworkException;
use Psr\Http\Server\MiddlewareInterface;
use Tomrf\ServiceContainer\ServiceContainer;

class RelayMiddlewareResolver
{
    public function resolveMiddleware(
        string $class,
        ServiceContainer $serviceContainer
    ): MiddlewareInterface {
        $instance = $serviceContainer->get($class);

        if (!$instance instanceof MiddlewareInterface) {
            throw new FrameworkException('Middleware must implement MiddlewareInterface');
        }

        return $instance;
    }
}
