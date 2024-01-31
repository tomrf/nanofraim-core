<?php

declare(strict_types=1);

namespace Nanofraim\Test;

use Nanofraim\Application;
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
        self::$app = self::createApplication();
    }

    public function testInstantiationOfApplication(): void
    {
        $this->assertInstanceOf(
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

        $this->assertInstanceOf(
            Application::class,
            $app
        );
    }

    public function testAppCreateServerRequestFromGlobals(): void
    {
        $request = self::$app->createServerRequestFromGlobals();

        $this->assertInstanceOf(
            ServerRequestInterface::class,
            $request
        );
    }

    public function testAppHandle(): void
    {
        $request = self::$app->createServerRequestFromGlobals();

        $response = self::$app->handle($request);

        $this->assertInstanceOf(
            ResponseInterface::class,
            $response
        );

        // verify http code 200
        $this->assertSame(
            200,
            $response->getStatusCode()
        );

        // verify X-Dummy-Middleware header
        $this->assertSame(
            'true',
            $response->getHeaderLine('X-Dummy-Middleware')
        );

        // verify body
        $this->assertSame(
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

    private static function createApplication(): Application
    {
        $serviceContainer = new ServiceContainer(
            new Autowire()
        );

        $serviceContainer->add(
            DummyMiddleware::class,
            static fn () => new DummyMiddleware(new ResponseFactory())
        );

        return new Application(
            $serviceContainer,
            new ConfigContainer([
                'middleware' => [
                    DummyMiddleware::class,
                ],
            ]),
            new ResponseEmitter(),
        );
    }
}
