<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\AbstractMiddleware;
use LDL\Http\Router\Plugin\LDL\Cache\Config\RouteCacheConfig;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\CacheKeyGeneratorInterface;
use LDL\Http\Router\Route\RouteInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface as CacheAdapterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class CachePostDispatch extends AbstractMiddleware
{
    /**
     * @var CacheAdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var RouteCacheConfig
     */
    private $cacheConfig;

    /**
     * @var CacheKeyGeneratorInterface
     */
    private $cacheKeyGenerator;

    public function init(
        CacheAdapterInterface $cacheAdapter,
        RouteCacheConfig $cacheConfig,
        CacheKeyGeneratorInterface $keyGenerator
    ) : self
    {
        $this->cacheAdapter = $cacheAdapter;
        $this->cacheConfig = $cacheConfig;
        $this->cacheKeyGenerator = $keyGenerator;

        return $this;
    }

    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        RouteInterface $route = null,
        ParameterBag $urlParameters = null
    ) : ?array
    {
        $router = $route->getRouter();
        $response->getHeaderBag()->set('X-Cache-Hit',0);

        $storageKey = sprintf(
            '%s.%s',
            $router->getResponseParserRepository()->getSelectedKey(),
            $this->cacheKeyGenerator->generate($route, $urlParameters)
        );

        $item = $this->cacheAdapter->getItem($storageKey);

        $expires = 0;

        if($this->cacheConfig->getExpiresAt()){
            $now = new \DateTime('now', new \DateTimeZone('UTC'));
            $expires = $now->add($this->cacheConfig->getExpiresAt());
            $item->expiresAfter($this->cacheConfig->getExpiresAt());
            $response->setExpires($expires);
        }

        $encode = [
            'expires' => $expires,
            'data' => $route->getRouter()->getDispatcher()->getResult()
        ];

        $item->set($encode);

        $this->cacheAdapter->save($item);
        $this->cacheAdapter->commit();

        return null;
    }

}