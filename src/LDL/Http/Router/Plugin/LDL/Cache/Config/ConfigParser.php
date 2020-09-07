<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Config;

use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\PreDispatch;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\PostDispatch;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserInterface;
use LDL\Http\Router\Route\Route;
use Psr\Container\ContainerInterface;
use LDL\Http\Router\Helper\ClassOrContainer;

class ConfigParser implements RouteConfigParserInterface
{
    private const DEFAULT_IS_ACTIVE = true;
    private const DEFAULT_PRIORITY = 1;

    public function parse(array $data, Route $route, ContainerInterface $container = null, string $file = null): void
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

        $route->getConfig()->getPreDispatchMiddleware()->append(
            new PreDispatch($isActive, $priority, $cacheAdapter, $cacheConfig)
        );

        $route->getConfig()->getPostDispatchMiddleware()->append(
            new PostDispatch($isActive, $priority, $cacheAdapter, $cacheConfig)
        );
    }
}