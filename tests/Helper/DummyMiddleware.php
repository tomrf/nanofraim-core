<?php

declare(strict_types=1);

namespace Nanofraim\Test\Helper;

use Nanofraim\Http\AbstractMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DummyMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse(200);
        $response = $response->withHeader('X-Dummy-Middleware', 'true');

        $body = $response->getBody();
        $body->write('Hello World');

        return $response->withBody($body);
    }
}
