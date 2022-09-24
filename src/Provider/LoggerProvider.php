<?php

declare(strict_types=1);

namespace Nanofraim\Provider;

use Nanofraim\AbstractProvider;
use Tomrf\Logger\Logger;

class LoggerProvider extends AbstractProvider
{
    public function createService(): \Psr\Log\LoggerInterface
    {
        $path = $this->config->get('path');

        return new Logger(
            is_scalar($path) ? (string) $path : null,
        );
    }
}
