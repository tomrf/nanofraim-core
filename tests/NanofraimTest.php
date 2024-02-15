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
 * @covers \Nanofraim\Exception\FrameworkException
 * @covers \Nanofraim\Http\AbstractMiddleware
 * @covers \Nanofraim\Http\AbstractController
 * @covers \Nanofraim\Http\ResponseEmitter
 * @covers \Nanofraim\Http\ResponseFactory
 * @covers \Nanofraim\Provider\LoggerProvider
 * @covers \Nanofraim\Provider\ResponseFactoryProvider
 * @covers \Nanofraim\Provider\SessionProvider
 * @covers \Nanofraim\Provider\SimpleCacheProvider
 * @covers \Nanofraim\Application
 * @covers \Nanofraim\AbstractProvider
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

    public function testAbstractController(): void
    {
        $controller = new class (
            self::$app->createServerRequestFromGlobals(),
            new ResponseFactory()
        ) extends \Nanofraim\Http\AbstractController {
            public function __invoke(): ResponseInterface
            {
                return $this->responseFactory->createResponse(200, 'Hello World');
            }
        };

        $response = $controller();

        self::assertInstanceOf(
            ResponseInterface::class,
            $response
        );

        // verify http code 200
        self::assertSame(
            200,
            $response->getStatusCode()
        );
    }

    public function testResponseEmitter(): void
    {
        $request = self::$app->createServerRequestFromGlobals()->withAttribute('X-Dummy-Middleware', true);
        $response = self::$app->handle($request);

        self::$app->emit($response);

        // check body output
        $this->expectOutputRegex('/X-Dummy-Middleware: true/');
    }

    public function testProviderLoggerProvider(): void
    {
        $tempLogFile = tempnam(sys_get_temp_dir(), '__NanofraimTest_');

        $loggerProvider = new \Nanofraim\Provider\LoggerProvider(
            new ConfigContainer([
                'path' => $tempLogFile,
            ]),
            new ResponseFactory()
        );

        $logger = $loggerProvider->createService();

        $logger->info('Hello World');

        self::assertStringContainsString(
            'Hello World',
            file_get_contents($tempLogFile)
        );

        unlink($tempLogFile);
    }

    public function testProviderSessionProvider(): void
    {
        $sessionProvider = new \Nanofraim\Provider\SessionProvider(
            new ConfigContainer([
                'name' => 'NanofraimTest',
            ]),
            new ResponseFactory()
        );

        $session = $sessionProvider->createService();

        $session->set('foo', 'bar');

        self::assertSame(
            'bar',
            $session->get('foo')
        );
    }

    public function testProviderResponseFactoryProvider(): void
    {
        $responseFactoryProvider = new \Nanofraim\Provider\ResponseFactoryProvider(
            new ConfigContainer(),
        );

        $responseFactory = $responseFactoryProvider->createService();

        $response = $responseFactory->createResponse(123, 'Test');

        self::assertInstanceOf(
            ResponseInterface::class,
            $response
        );

        self::assertSame(
            123,
            $response->getStatusCode()
        );
    }


    public function testProviderSimpleCacheProvider(): void
    {
        $simpleCacheProvider = new \Nanofraim\Provider\SimpleCacheProvider(
            new ConfigContainer([
                'adapter' => 'array'
            ]),
        );

        $simpleCache = $simpleCacheProvider->createService();

        $simpleCache->set('foo', 'bar');

        self::assertSame(
            'bar',
            $simpleCache->get('foo')
        );
    }
}
