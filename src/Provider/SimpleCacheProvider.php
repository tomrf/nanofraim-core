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
        $root = $this->config->get('adapter.filesystem.root');
        if (null === $root) {
            throw new FrameworkException('No root path defined for filesystem cache adapter');
        }

        $directory = $this->config->get('adapter.filesystem.directory');
        if (null === $directory) {
            throw new FrameworkException('No directory defined for filesystem cache adapter');
        }

        // make sure $root and $directory can be cast to string
        if (!is_scalar($root) || !is_scalar($directory)) {
            throw new FrameworkException('Root and directory must be scalar values');
        }

        $cachePath = sprintf(
            '%s/%s',
            $root,
            $directory,
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
            false
        );

        // make sure all variables are scalar
        if (!is_scalar($host) || !is_scalar($port) || !is_scalar($timeout) || !is_scalar($auth)) {
            throw new FrameworkException('Redis cache adapter configuration is invalid, contains non-scalar values');
        }

        try {
            $client = new Redis();
        } catch (\Exception $e) {
            throw new FrameworkException(
                'Could not create instance of Redis: '.$e->getMessage()
            );
        }

        try {
            $client->connect((string) $host, (int) $port, (int) $timeout);
        } catch (\RedisException $e) {
            throw new FrameworkException(
                'Could not connect to redis: '.$e->getMessage()
            );
        }

        if (false !== $auth) {
            if (false === $client->auth((string) $auth)) {
                throw new FrameworkException(
                    'Redis authentication failed: '.$client->getLastError()
                );
            }
        }

        return new RedisCachePool($client);
    }
}
