<?php

declare(strict_types=1);

namespace Nanofraim\Provider;

use Nanofraim\AbstractProvider;
use Psr\Log\LoggerInterface;
use Tomrf\Logger\Logger;

use function is_scalar;

class LoggerProvider extends AbstractProvider
{
    public function createService(): LoggerInterface
    {
        $path = $this->config->get('path');

        $stream = null;

        if (is_scalar($path)) {
            $stream = fopen((string)$path, 'a');
        }

        return new Logger($stream);
    }
}
