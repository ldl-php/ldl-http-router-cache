<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Framework\Base\Traits\IsActiveInterfaceTrait;
use LDL\Framework\Base\Traits\NamespaceInterfaceTrait;
use LDL\Framework\Base\Traits\PriorityInterfaceTrait;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\MiddlewareInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Config\RouteCacheConfig;
use LDL\Http\Router\Route\Route;
use LDL\Http\Router\Route\RouteInterface;
use LDL\Http\Router\Router;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheAdapterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class PostDispatch implements MiddlewareInterface
{
    private const NAMESPACE = 'LDLPlugin';
    private const NAME = 'RouteCachePostDispatch';

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
     * @var Router
     */
    private $router;

    public function __construct(
        bool $isActive,
        int $priority,
        Router $router,
        CacheAdapterInterface $cacheAdapter,
        RouteCacheConfig $cacheConfig
    )
    {
        $this->_tActive = $isActive;
        $this->_tPriority = $priority;
        $this->_tNamespace = self::NAMESPACE;
        $this->_tName = self::NAME;

        $this->router = $router;
        $this->cacheAdapter = $cacheAdapter;
        $this->cacheConfig = $cacheConfig;
    }

    public function dispatch(
        RouteInterface $route,
        RequestInterface $request,
        ResponseInterface $response,
        ParameterBag $urlParameters = null
    ) : ?array
    {
        $response->getHeaderBag()->set('X-Cache-Hit',0);

        /**
         * @var RouteCacheKeyInterface $dispatcher
         */
        $dispatcher = $route->getConfig()->getDispatcher();

        $key = sprintf(
            '%s.%s',
            $dispatcher->getCacheKey($route, $request, $response),
            $this->router->getResponseParserRepository()->getSelectedKey()
        );

        $item = $this->cacheAdapter->getItem($key);

        $expires = 0;

        if($this->cacheConfig->getExpiresAt()){
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $expires = $now->add($this->cacheConfig->getExpiresAt());
            $item->expiresAfter($this->cacheConfig->getExpiresAt());
            $response->setExpires($expires);
        }

        $encode = ['expires' => $expires, 'data' => $this->router->getDispatcher()->getResult()];

        $item->set($encode);
        $this->cacheAdapter->save($item);
        $this->cacheAdapter->commit();
        return null;
    }
}