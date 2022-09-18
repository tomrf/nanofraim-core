<?php

declare(strict_types=1);

namespace Nanofraim\Test;

use Nanofraim\Http\ResponseEmitter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @covers \Nanofraim\Application
 */
final class NanofraimTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // ...
    }

    public function testInstantiationOfApplication(): void
    {
        static::assertInstanceOf(
            \Nanofraim\Application::class,
            new \Nanofraim\Application(
                new \Tomrf\ServiceContainer\ServiceContainer(
                    new \Tomrf\Autowire\Autowire()
                ),
                new \Tomrf\ConfigContainer\ConfigContainer([
                    'middleware' => [
                        \Nanofraim\Test\Helper\DummyMiddleware::class,
                    ],
                ]),
                new ResponseEmitter(),
            )
        );
    }
}
