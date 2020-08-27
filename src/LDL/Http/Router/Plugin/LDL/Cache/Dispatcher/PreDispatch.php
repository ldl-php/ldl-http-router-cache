<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Config\RouteCacheConfig;
use LDL\Http\Router\Route\Middleware\MiddlewareInterface;
use LDL\Http\Router\Route\Route;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheAdapterInterface;

class PreDispatch implements MiddlewareInterface
{
    private const PURGE_SECRET_HEADER = 'X-HTTP-CACHE-SECRET';

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var CacheAdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var RouteCacheConfig
     */
    private $cacheConfig;

    public function __construct(
        bool $isActive,
        int $priority,
        CacheAdapterInterface $cacheAdapter,
        RouteCacheConfig $cacheConfig
    )
    {
        $this->isActive = $isActive;
        $this->priority = $priority;
        $this->cacheAdapter = $cacheAdapter;
        $this->cacheConfig = $cacheConfig;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function dispatch(Route $route, RequestInterface $request, ResponseInterface $response): void
    {
        var_dump('PRE DISPATCH');
        /**
         * @var CacheableInterface $dispatcher
         */
        $dispatcher = $route->getConfig()->getDispatcher();

        $headers = $request->getHeaderBag();

        $providedCacheKey = $headers->get(self::PURGE_SECRET_HEADER);

        $key = $dispatcher->getCacheKey($route, $request, $response);

        $now = new \DateTime('now');

        $item = $this->cacheAdapter->getItem($key);

        if(!$item->isHit()) {
            return;
        }

        if(
            $request->isPurge() &&
            $this->cacheConfig->getSecretKey() &&
            $this->cacheConfig->getSecretKey() === $providedCacheKey
        ){
            $this->cacheAdapter->deleteItem($key);
        }

        if($request->isPurge() && null === $this->cacheConfig->getSecretKey()){
            $this->cacheAdapter->deleteItem($key);
        }

        $value = $item->get();

        if($now > $value['expires']){
            $this->cacheAdapter->deleteItem($item);
            return;
        }

        $response->setExpires($value['expires']);
        $response->setContent($value['data']);
    }
}