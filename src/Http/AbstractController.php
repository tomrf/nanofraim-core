<?php

declare(strict_types=1);

namespace Nanofraim\Http;

use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractController
{
    public function __construct(
        protected ServerRequestInterface $request,
        protected ResponseFactory $responseFactory,
    ) {}
}
