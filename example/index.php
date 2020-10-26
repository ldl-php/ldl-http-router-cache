<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\Http\Core\Request\Request;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\Response;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserCollection;
use LDL\Http\Router\Route\Factory\RouteFactory;
use LDL\Http\Router\Route\Group\RouteGroup;
use LDL\Http\Router\Route\RouteInterface;
use LDL\Http\Router\Router;
use LDL\Http\Router\Plugin\LDL\Cache\Config\CacheConfigParser;
use LDL\Http\Router\Middleware\AbstractMiddleware;
use LDL\Http\Router\Plugin\LDL\Cache\Adapter\CacheAdapterCollection;
use LDL\Http\Router\Plugin\LDL\Cache\Adapter\CacheAdapterCollectionItem;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\CacheKeyGeneratorCollection;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\StaticCacheKeyGenerator;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\ParameterBag;

class CacheDispatcherTest extends AbstractMiddleware
{
    public function isActive(): bool
    {
        return true;
    }

    public function getPriority(): int
    {
        return 1;
    }

    public function dispatch(
        RouteInterface $route,
        RequestInterface $request,
        ResponseInterface $response,
        ParameterBag $urlParameters=null
    ) : ?array
    {
        return [
            'name' => $urlParameters->get('name')
        ];
    }
}

$cacheKeyGenerators = new CacheKeyGeneratorCollection();
$cacheKeyGenerators->append(new StaticCacheKeyGenerator('static.key', true));

$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response
);

$cacheAdapters = new CacheAdapterCollection();
$cacheAdapters->append(new CacheAdapterCollectionItem(new FilesystemAdapter(), 'fs.adapter'));

$parserCollection = new RouteConfigParserCollection();
$parserCollection->append(
    new CacheConfigParser(
        $cacheAdapters,
        $cacheKeyGenerators
    )
);

$router->getDispatcherChain()
    ->append(new CacheDispatcherTest('test.cache.dispatcher'));

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