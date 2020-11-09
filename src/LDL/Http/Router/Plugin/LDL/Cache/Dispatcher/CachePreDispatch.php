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

class CachePreDispatch extends AbstractMiddleware
{
    private const PURGE_SECRET_HEADER = 'X-Http-Cache-Secret';
    public const NAME = 'ldl.router.cache.preDispatch';

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
        bool $isActive,
        int $priority,
        CacheAdapterInterface $cacheAdapter,
        RouteCacheConfig $cacheConfig,
        CacheKeyGeneratorInterface $cacheKeyGenerator
    ) : void
    {
        $this->cacheAdapter = $cacheAdapter;
        $this->cacheConfig = $cacheConfig;
        $this->cacheKeyGenerator = $cacheKeyGenerator;
        $this->setActive($isActive);
        $this->setPriority($priority);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        RouteInterface $route = null,
        ParameterBag $urlParams = null
    ) : ?array
    {
        $router = $route->getRouter();
        $headers = $request->getHeaderBag();

        $providedCacheKey = $headers->get(self::PURGE_SECRET_HEADER);

        $storageKey = sprintf(
            '%s.%s',
            $router->getResponseParserRepository()->getSelectedKey(),
            $this->cacheKeyGenerator->generate($router->getCurrentRoute(), $urlParams)
        );

        $now = new \DateTime('now');

        $item = $this->cacheAdapter->getItem($storageKey);

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
            $this->cacheAdapter->deleteItem($storageKey);
        }

        /**
         * Cache can be deleted by anyone without the use of a secret key
         */
        if($isPurge && null === $this->cacheConfig->getSecretKey()){
            $this->cacheAdapter->deleteItem($storageKey);
        }

        $value = $item->get();

        if($now > $value['expires']){
            $this->cacheAdapter->deleteItem($storageKey);
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