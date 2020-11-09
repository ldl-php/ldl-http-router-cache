<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Config;

use LDL\Http\Router\Plugin\LDL\Cache\Adapter\CacheAdapterCollectionInterface;
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
        RouteInterface $route
    ): void
    {
        $rawConfig = $route->getConfig()->getRawConfig();

        if(!array_key_exists('cache', $rawConfig)){
            return;
        }

        $this->adapters->select($rawConfig['cache']['adapter']);

        /**
         * @var CacheAdapterCollectionItemInterface $adapter
         */
        $adapter = $this->adapters->getSelectedItem();

        $this->adapters->lockSelection();

        $cacheConfig = RouteCacheConfig::fromArray($rawConfig['cache']['config']);

        if($cacheConfig->getKeyGenerator()){
            $this->keyGenerators->select($cacheConfig->getKeyGenerator());
        }

        $this->keyGenerators->lockSelection();

        $preDispatch = $route->getDispatchChain()->filterByClass(CachePreDispatch::class);

        if(count($preDispatch) > 0){
            $route->getPreDispatchChain()->append($preDispatch);

            $preDispatch->init(
                true,
                1,
                $adapter->getAdapter(),
                $cacheConfig,
                $this->keyGenerators->getSelectedItem()
            );
        }

        $postDispatch = $route->getDispatchChain()->filterByClass(CachePostDispatch::class);

        if(count($postDispatch) > 0){
            $route->getPostDispatchChain()->append($postDispatch);

            $postDispatch->init(
                true,
                9999,
                $adapter->getAdapter(),
                $cacheConfig,
                $this->keyGenerators->getSelectedItem()
            );
        }

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