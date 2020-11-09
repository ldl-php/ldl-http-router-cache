<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\Http\Core\Request\Request;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\Response;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Middleware\DispatcherRepository;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserRepository;
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
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CachePreDispatch;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CachePostDispatch;

class CacheDispatcherTest extends AbstractMiddleware
{
    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        RouteInterface $route = null,
        ParameterBag $urlParams = null
    )
    {
        return [
            'name' => $urlParams->get('name')
        ];
    }
}

$cacheKeyGenerators = new CacheKeyGeneratorCollection();
$cacheKeyGenerators->append(new StaticCacheKeyGenerator('static.key', true));

$cacheAdapters = new CacheAdapterCollection();
$cacheAdapters->append(new CacheAdapterCollectionItem(new FilesystemAdapter(), 'fs.adapter'));

$parserCollection = new RouteConfigParserRepository();
$parserCollection->append(
    new CacheConfigParser(
        $cacheAdapters,
        $cacheKeyGenerators
    )
);

$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response,
    $parserCollection
);

$dispatcherRepository = new DispatcherRepository();
$dispatcherRepository->append(new CacheDispatcherTest('cacheDispatcher'))
->append(new CachePreDispatch('cachePreDispatch'))
->append(new CachePostDispatch('cachePostDispatch'));

try{
    $routes = RouteFactory::fromJsonFile(
        __DIR__.'/routes.json',
        $router,
        $dispatcherRepository
    );
}catch(\Exception $e){
    return $e->getMessage();
}

$group = new RouteGroup('test', 'test', $routes);

$router->addGroup($group);

$router->dispatch()->send();