<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Framework\Base\Traits\IsActiveInterfaceTrait;
use LDL\Framework\Base\Traits\NamespaceInterfaceTrait;
use LDL\Framework\Base\Traits\PriorityInterfaceTrait;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\MiddlewareInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Config\RouteCacheConfig;
use LDL\Http\Router\Response\Parser\Json\JsonResponseParser;
use LDL\Http\Router\Route\Route;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheAdapterInterface;

class PreDispatch implements MiddlewareInterface
{
    private const PURGE_SECRET_HEADER = 'X-HTTP-CACHE-SECRET';
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

    public function __construct(
        bool $isActive,
        int $priority,
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
    }

    public function dispatch(
        Route $route,
        RequestInterface $request,
        ResponseInterface $response,
        array $urlArguments = []
    ): void
    {
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

        $jsonParser = new JsonResponseParser();

        $response->setExpires($value['expires']);
        $response->setContent($jsonParser->parse($value['data']));
    }
}