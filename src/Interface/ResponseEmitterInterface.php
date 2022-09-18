<?php

declare(strict_types=1);

namespace Nanofraim\Interface;

use Psr\Http\Message\ResponseInterface;

interface ResponseEmitterInterface
{
    public function emit(ResponseInterface $response): void;
}
