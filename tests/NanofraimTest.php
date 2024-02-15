<?php

declare(strict_types=1);

namespace Nanofraim\Test;

use Nanofraim\Application;
use Nanofraim\Exception\FrameworkException;
use Nanofraim\Http\ResponseEmitter;
use Nanofraim\Http\ResponseFactory;
use Nanofraim\Test\Helper\DummyMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tomrf\Autowire\Autowire;
use Tomrf\ConfigContainer\ConfigContainer;
use Tomrf\ServiceContainer\ServiceContainer;

/**
 * @internal
 *
 * @covers \Nanofraim\Application
 * @covers \Nanofraim\Http\AbstractMiddleware
 * @covers \Nanofraim\Http\ResponseEmitter
 * @covers \Nanofraim\Http\ResponseFactory
 * @covers \Nanofraim\RelayMiddlewareResolver
 * @covers \Nanofraim\Test\Helper\DummyMiddleware
 */
final class NanofraimTest extends TestCase
{
    private static Application $app;

    public static function setUpBeforeClass(): void
    {
        $serviceContainer = new ServiceContainer(
            new Autowire()
        );

        $serviceContainer->add(
            DummyMiddleware::class,
            static fn () => new DummyMiddleware(new ResponseFactory())
        );

        self::$app = new Application(
            $serviceContainer,
            new ConfigContainer([
                'middleware' => [
                    DummyMiddleware::class,
                ],
            ]),
            new ResponseEmitter(),
        );
    }

    public function testInstantiationOfApplication(): void
    {
        self::assertInstanceOf(
            Application::class,
            self::$app
        );
    }

    public function testAppConstructor(): void
    {
        $serviceContainer = new ServiceContainer(
            new Autowire()
        );

        $serviceContainer->add(
            DummyMiddleware::class,
            static fn () => new DummyMiddleware(new ResponseFactory())
        );

        $app = new Application(
            $serviceContainer,
            new ConfigContainer([
                'middleware' => [
                    DummyMiddleware::class,
                ],
            ]),
            new ResponseEmitter(),
        );

        self::assertInstanceOf(
            Application::class,
            $app
        );
    }

    public function testAppCreateServerRequestFromGlobals(): void
    {
        $request = self::$app->createServerRequestFromGlobals();

        self::assertInstanceOf(
            ServerRequestInterface::class,
            $request
        );
    }

    public function testAppHandle(): void
    {
        $request = self::$app->createServerRequestFromGlobals();

        $response = self::$app->handle($request);

        self::assertInstanceOf(
            ResponseInterface::class,
            $response
        );

        // verify http code 200
        self::assertSame(
            200,
            $response->getStatusCode()
        );

        // verify X-Dummy-Middleware header
        self::assertSame(
            'true',
            $response->getHeaderLine('X-Dummy-Middleware')
        );

        // verify body
        self::assertSame(
            'Hello World',
            (string) $response->getBody()
        );
    }

    public function testAppEmit(): void
    {
        $this->expectsOutput();

        $request = self::$app->createServerRequestFromGlobals();
        $response = self::$app->handle($request);

        self::$app->emit($response);

        // check body output
        $this->expectOutputRegex('/Hello World/');
    }

    public function testAppWithNoMiddleware(): void
    {
        $this->expectExceptionMessage('No middleware defined in config, unable to create Relay instance');
        $this->expectException(FrameworkException::class);

        new Application(
            new ServiceContainer(
                new Autowire()
            ),
            new ConfigContainer([
                'middleware' => null,
            ]),
            new ResponseEmitter(),
        );
    }

    public function testAppWithNonArrayMiddleware(): void
    {
        $this->expectExceptionMessage('Middleware must be an array, found string');
        $this->expectException(FrameworkException::class);

        new Application(
            new ServiceContainer(
                new Autowire()
            ),
            new ConfigContainer([
                'middleware' => 'not an array',
            ]),
            new ResponseEmitter(),
        );
    }

    public function testAppWithMiddlewareNotImplementingMiddlewareInterface(): void
    {
        $this->expectExceptionMessage('Middleware must implement MiddlewareInterface');
        $this->expectException(FrameworkException::class);

        $serviceContainer = new ServiceContainer(
            new Autowire()
        );

        $serviceContainer->add(
            \stdClass::class,
            static fn () => new \stdClass()
        );

        $app = new Application(
            $serviceContainer,
            new ConfigContainer([
                'middleware' => [
                    \stdClass::class,
                ],
            ]),
            new ResponseEmitter(),
        );

        $request = $app->createServerRequestFromGlobals();

        $app->handle($request);
    }
}
