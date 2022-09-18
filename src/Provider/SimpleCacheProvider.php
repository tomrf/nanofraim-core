<?php

declare(strict_types=1);

namespace Nanofraim\Provider;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Adapter\Redis\RedisCachePool;
use Cache\Adapter\Void\VoidCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Nanofraim\AbstractProvider;
use Nanofraim\Exception\FrameworkException;
use Psr\SimpleCache\CacheInterface;
use Redis;

class SimpleCacheProvider extends AbstractProvider
{
    public function createService(): CacheInterface
    {
        $cacheAdapter = $this->config->get('adapter', 'void');

        switch ($cacheAdapter) {
            case 'array':
                $pool = new ArrayCachePool();

                break;

            case 'void':
                $pool = new VoidCachePool();

                break;

            case 'redis':
                $pool = $this->createRedisPool();
                break;

            case 'filesystem':
                $pool = $this->createFilesystemPool();

                break;

            default:
                throw new FrameworkException('Unknown cache adapter: '.$cacheAdapter);
        }

        return new SimpleCacheBridge($pool);
    }

    private function createFilesystemPool(): FilesystemCachePool
    {
        $cachePath = sprintf(
            '%s/%s',
            $this->config->get('adapters.filesystem.root'),
            $this->config->get('adapters.filesystem.directory'),
        );

        if ('/' === $cachePath) {
            throw new FrameworkException('Filesystem cache path not configured');
        }

        return new FilesystemCachePool(
            new Filesystem(new Local($cachePath)),
        );
    }

    private function createRedisPool(): RedisCachePool
    {
        $host = $this->config->get(
            'adapters.redis.hostname',
            '127.0.0.1',
        );

        $port = $this->config->get(
            'adapters.redis.port',
            6379,
        );

        $timeout = $this->config->get(
            'adapters.redis.timeout',
            0,
        );

        $auth = $this->config->get(
            'adapters.redis.auth',
        );

        try {
            $client = new Redis();
        } catch (\Exception $e) {
            throw new FrameworkException(
                'Could not create instance of Redis: '.$e->getMessage()
            );
        }

        try {
            $client->connect($host, $port, $timeout);
        } catch (\RedisException $e) {
            throw new FrameworkException(
                'Could not connect to redis: '.$e->getMessage()
            );
        }

        if ($auth) {
            if (false === $client->auth($auth)) {
                throw new FrameworkException(
                    'Redis authentication failed: '.$client->getLastError()
                );
            }
        }

        return new RedisCachePool($client);

    }
}
