<?php

declare(strict_types=1);

namespace Nanofraim;

use Nanofraim\Exception\FrameworkException;
use Nanofraim\Interface\ResponseEmitterInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Relay\Relay;
use Tomrf\ServiceContainer\ServiceContainer;

class Application
{
    private Relay $relay;

    public function __construct(
        protected ServiceContainer $serviceContainer,
        protected ContainerInterface $configContainer,
        protected ResponseEmitterInterface $responseEmitter,
    ) {
        $this->relay = $this->createRelay();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->relay->handle($request);
    }

    public function emit(ResponseInterface $response): void
    {
        $this->responseEmitter->emit($response);
    }

    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $serverRequestCreator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
        );

        return $serverRequestCreator->fromGlobals();
    }

    private function createRelay(): Relay
    {
        $serviceContainer = $this->serviceContainer;

        $middleware = $this->configContainer->get('middleware');
        if (null === $middleware) {
            throw new FrameworkException('No middleware defined in config, unable to create Relay instance');
        }

        if (!\is_array($middleware)) {
            throw new FrameworkException('Middleware must be an array, found '.\gettype($middleware));
        }

        $middlewareResolver = new RelayMiddlewareResolver();

        return new Relay(
            $middleware,
            fn ($class): MiddlewareInterface => $middlewareResolver->resolveMiddleware($class, $serviceContainer)
        );
    }
}
