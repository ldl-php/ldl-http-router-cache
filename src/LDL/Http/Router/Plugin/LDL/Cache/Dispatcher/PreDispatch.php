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

    /**
     * @var ResponseParserRepositoryInterface
     */
    private $responseParserRepository;

    public function __construct(
        bool $isActive,
        int $priority,
        CacheAdapterInterface $cacheAdapter,
        RouteCacheConfig $cacheConfig,
        ResponseParserRepositoryInterface $responseParserRepository
    )
    {
        $this->_tActive = $isActive;
        $this->_tPriority = $priority;
        $this->_tNamespace = self::NAMESPACE;
        $this->_tName = self::NAME;

        $this->cacheAdapter = $cacheAdapter;
        $this->cacheConfig = $cacheConfig;
        $this->responseParserRepository = $responseParserRepository;
    }

    public function dispatch(
        Route $route,
        RequestInterface $request,
        ResponseInterface $response,
        array $urlArguments = []
    ): void
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
            $this->responseParserRepository->getSelectedKey()
        );

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
    }
}