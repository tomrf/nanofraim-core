<?php

declare(strict_types=1);

namespace Nanofraim\Test\Helper;

class DummyMiddleware extends \Nanofraim\Http\AbstractMiddleware
{
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
    {
        return $this->responseFactory->createResponse(200);
    }
}
