<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Framework\Base\Traits\IsActiveInterfaceTrait;
use LDL\Framework\Base\Traits\NamespaceInterfaceTrait;
use LDL\Framework\Base\Traits\PriorityInterfaceTrait;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\MiddlewareInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Config\RouteCacheConfig;
use LDL\Http\Router\Response\Parser\Repository\ResponseParserRepositoryInterface;
use LDL\Http\Router\Route\Route;
use LDL\Http\Router\Route\RouteInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheAdapterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class PreDispatch implements MiddlewareInterface
{
    private const PURGE_SECRET_HEADER = 'X-Http-Cache-Secret';
    private const NAMESPACE = 'LDLPlugin';
    private const NAME = 'RouteCachePreDispatch';

    use NamespaceInterfaceTrait;
    use IsActiveInterfaceTrait;
    use PriorityInterfaceTrait;

    /**
     * @var CacheAdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var RouteCacheConfig
     */
    private $cacheConfig;

    /**
     * @var string
     */
    private $cacheKey;

    public function __construct(
        bool $isActive,
        int $priority,
        string $cacheKey,
        CacheAdapterInterface $cacheAdapter,
        RouteCacheConfig $cacheConfig
    )
    {
        $this->_tActive = $isActive;
        $this->_tPriority = $priority;
        $this->_tNamespace = self::NAMESPACE;
        $this->_tName = self::NAME;

        $this->cacheAdapter = $cacheAdapter;
        $this->cacheConfig = $cacheConfig;
        $this->cacheKey = $cacheKey;
    }

    public function dispatch(
        RouteInterface $route,
        RequestInterface $request,
        ResponseInterface $response,
        ParameterBag $urlParameters = null
    ) : ?array
    {
        /**
         * @var RouteCacheKeyInterface $dispatcher
         */
        $dispatcher = $route->getConfig()->getDispatcher();

        $headers = $request->getHeaderBag();

        $providedCacheKey = $headers->get(self::PURGE_SECRET_HEADER);

        $key = sprintf(
            '%s.%s',
            $dispatcher->getCacheKey($route, $request, $response),
            $this->cacheKey
        );

        $now = new \DateTime('now');

        $item = $this->cacheAdapter->getItem($key);

        $isPurge = $request->isPurge();

        /**
         * Data will be handled by the post dispatcher and stored in the cache adapter
         */
        if(!$item->isHit()) {
            return null;
        }

        /**
         * Cache can be deleted only when the request provides the secret key
         */
        if(
            $isPurge &&
            $this->cacheConfig->getSecretKey() &&
            $this->cacheConfig->getSecretKey() === $providedCacheKey
        ){
            $this->cacheAdapter->deleteItem($key);
        }

        /**
         * Cache can be deleted by anyone without the use of a secret key
         */
        if($isPurge && null === $this->cacheConfig->getSecretKey()){
            $this->cacheAdapter->deleteItem($key);
        }

        $value = $item->get();

        if($now > $value['expires']){
            $this->cacheAdapter->deleteItem($item);
            return null;
        }

        /**
         * We need to throw a CacheHitException to break the chain of execution.
         * If we don't throw, everything else in the chain will get executed, ruining the whole purpose of caching.
         */
        if(!$isPurge){
            throw new CacheHitException('Cache hit');
        }

        return null;
    }
}