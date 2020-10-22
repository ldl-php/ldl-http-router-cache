<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\Http\Core\Request\Request;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\Response;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserCollection;
use LDL\Http\Router\Route\Dispatcher\RouteDispatcherInterface;
use LDL\Http\Router\Route\Factory\RouteFactory;
use LDL\Http\Router\Route\Group\RouteGroup;
use LDL\Http\Router\Route\RouteInterface;
use LDL\Http\Router\Router;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\RouteCacheKeyInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Config\CacheConfigParser;
use Symfony\Component\HttpFoundation\ParameterBag;

class Dispatcher implements RouteDispatcherInterface, RouteCacheKeyInterface
{

    public function getCacheKey(
        RouteInterface $route,
        RequestInterface $request,
        ResponseInterface $response
    ): string
    {
        return 'test2.something';
    }

    public function dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        ParameterBag $urlParameters=null
    ) : ?array
    {
        return [
            'name' => $request->get('name')
        ];
    }
}


$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response
);


$parserCollection = new RouteConfigParserCollection();
$parserCollection->append(new CacheConfigParser($router));

try{
    $routes = RouteFactory::fromJsonFile(
        __DIR__.'/routes.json',
        $router,
        null,
        $parserCollection
    );
}catch(\Exception $e){
    return $e->getMessage();
}

$group = new RouteGroup('test', 'test', $routes);

$router->addGroup($group);

$router->dispatch()->send();