<?php declare(strict_types=1);

namespace LDL\Http\Router\Dispatcher;

use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Cache\CacheableInterface;
use LDL\Http\Router\Route\Dispatcher\RouteDispatcherInterface;
use LDL\Http\Router\Route\Parameter\ParameterCollection;
use LDL\Http\Router\Route\RouteInterface;

class Dispatch implements RouteDispatcherInterface, CacheableInterface
{
    public function getCacheKey(
        RouteInterface $route,
        RequestInterface $request,
        ResponseInterface $response
    ): string
    {
        return 'test';
    }

    public function dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        ParameterCollection $parameters = null,
        ParameterCollection $urlParameters = null
    ) : array
    {
        return [
            'converted' => $parameters->get('name')->getConvertedValue()
        ];
    }
}