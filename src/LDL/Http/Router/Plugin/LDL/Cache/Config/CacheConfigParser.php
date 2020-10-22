<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Config;

use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CacheHitExceptionHandler;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\PreDispatch;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\PostDispatch;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\RouteInterface;
use LDL\Http\Router\Router;
use Psr\Container\ContainerInterface;
use LDL\Http\Router\Helper\ClassOrContainer;

class CacheConfigParser implements RouteConfigParserInterface
{
    private const DEFAULT_IS_ACTIVE = true;
    private const DEFAULT_PRIORITY = 1;

    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function parse(
        array $data,
        RouteInterface $route,
        ContainerInterface $container = null,
        string $file = null
    ): void
    {
        if(!array_key_exists('cache', $data)){
            return;
        }

        $cacheAdapter = ClassOrContainer::get($data['cache']['adapter'], $container);

        $isActive = self::DEFAULT_IS_ACTIVE;

        if(array_key_exists('active', $data['cache'])){
            $isActive = (bool) $data['cache']['active'];
        }

        $priority = self::DEFAULT_PRIORITY;

        if(array_key_exists('priority', $data['cache'])){
            $priority = (int) $data['cache']['priority'];
        }

        $cacheConfig = RouteCacheConfig::fromArray($data['cache']['config']);

        $this->router->getPreDispatchMiddleware()->append(
            new PreDispatch(
                $isActive,
                $priority,
                $this->router->getResponseParserRepository()->getSelectedKey(),
                $cacheAdapter,
                $cacheConfig
            )
        );

        $this->router->getPostDispatchMiddleware()
            ->append(
                new PostDispatch(
                    $isActive,
                    $priority,
                    $this->router,
                    $cacheAdapter,
                    $cacheConfig
                )
            );

        $this->router
            ->getExceptionHandlerCollection()
            ->append(new CacheHitExceptionHandler($cacheAdapter));
    }
}