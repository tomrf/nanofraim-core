<?php

declare(strict_types=1);

namespace Nanofraim\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory
{
    protected Psr17Factory $factory;

    public function __construct()
    {
        $this->factory = new Psr17Factory();
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->factory->createResponse($code, $reasonPhrase);
    }
}
