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
        RouteInterface $route,
        string $file=null
    ): void
    {
        $cachePreDispatchers = $route->getPreDispatchChain()->filterByClass(CachePreDispatch::class);
        $cachePostDispatchers = $route->getPostDispatchChain()->filterByClass(CachePostDispatch::class);

        $hasPreDispatchers = count($cachePreDispatchers) > 0;
        $hasPostDispatchers = count($cachePostDispatchers) > 0;

        if(false === $hasPreDispatchers || false === $hasPostDispatchers){
            return;
        }

        $config = $route->getConfig()->getRawConfig();

        if(!array_key_exists('cache', $config)){
            return;
        }

        $this->adapters->select($config['cache']['adapter']);

        /**
         * @var CacheAdapterCollectionItemInterface $adapter
         */
        $adapter = $this->adapters->getSelectedItem();

        $this->adapters->lockSelection();

        $cacheConfig = RouteCacheConfig::fromArray($config['cache']['config']);

        if($cacheConfig->getKeyGenerator()){
            $this->keyGenerators->select($cacheConfig->getKeyGenerator());
        }

        $this->keyGenerators->lockSelection();

        /**
         * @var CachePreDispatch $preDispatch
         */
        foreach($cachePreDispatchers as $preDispatch){
            $preDispatch->init(
                $adapter->getAdapter(),
                $cacheConfig,
                $this->keyGenerators->getSelectedItem()
            );
        }

        /**
         * @var CachePostDispatch $postDispatch
         */
        foreach($cachePostDispatchers as $postDispatch){
            $postDispatch->init(
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