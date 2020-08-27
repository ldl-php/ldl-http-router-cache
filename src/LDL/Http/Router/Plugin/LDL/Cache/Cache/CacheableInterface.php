<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Cache;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Route\RouteInterface;

interface CacheableInterface
{
    /**
     * @param RouteInterface $route
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return string
     */
    public function getCacheKey(
        RouteInterface $route,
        RequestInterface $request,
        ResponseInterface $response
    ) : string;
}