<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Config;

use LDL\Http\Router\Plugin\LDL\Cache\Adapter\CacheAdapterCollectionInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Adapter\CacheAdapterCollectionItem;
use LDL\Http\Router\Plugin\LDL\Cache\Adapter\CacheAdapterCollectionItemInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CacheHitExceptionHandler;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CachePreDispatch;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CachePostDispatch;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\CacheKeyGeneratorCollectionInterface;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\RouteInterface;

class CacheConfigParser implements RouteConfigParserInterface
{
    /**
     * @var CacheAdapterCollectionInterface
     */
    private $adapters;

    /**
     * @var CacheKeyGeneratorCollectionInterface
     */
    private $keyGenerators;

    public function __construct(
        CacheAdapterCollectionInterface $adapters,
        CacheKeyGeneratorCollectionInterface $keyGenerators
    )
    {
        $this->adapters = $adapters;
        $this->keyGenerators = $keyGenerators;
    }

    public function parse(
        array $data,
        RouteInterface $route,
        string $file=null
    ): void
    {
        if(!array_key_exists('cache', $data)){
            return;
        }

        $this->adapters->select($data['cache']['adapter']);

        /**
         * @var CacheAdapterCollectionItemInterface $adapter
         */
        $adapter = $this->adapters->getSelectedItem();

        $this->adapters->lockSelection();

        $cacheConfig = RouteCacheConfig::fromArray($data['cache']['config']);

        if($cacheConfig->getKeyGenerator()){
            $this->keyGenerators->select($cacheConfig->getKeyGenerator());
        }

        $this->keyGenerators->lockSelection();

        $route->getRouter()
            ->getPreDispatchMiddleware()
            ->append(
                new CachePreDispatch(
                    true,
                    1,
                    $adapter->getAdapter(),
                    $cacheConfig,
                    $this->keyGenerators->getSelectedItem()
                )
        );

        $route->getRouter()->getPostDispatchMiddleware()
            ->append(
                new CachePostDispatch(
                    true,
                    9999,
                    $adapter->getAdapter(),
                    $cacheConfig,
                    $this->keyGenerators->getSelectedItem()
                )
            );

        $route->getRouter()
            ->getExceptionHandlerCollection()
            ->append(
                new CacheHitExceptionHandler(
                    $adapter->getAdapter(),
                    $this->keyGenerators->getSelectedItem()
                )
            );
    }
}