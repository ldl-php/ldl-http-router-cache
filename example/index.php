<?php declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use LDL\Http\Core\Request\Request;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\Response;
use LDL\Http\Core\Response\ResponseInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Interfaces\NoCacheInterface;
use LDL\Http\Router\Route\Factory\RouteFactory;
use LDL\Http\Router\Route\Group\RouteGroup;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\RequestParameterValueCacheKeyGenerator;
use LDL\Http\Router\Router;
use LDL\Http\Router\Plugin\LDL\Cache\Config\CacheConfigParser;
use LDL\Http\Router\Middleware\AbstractMiddleware;
use LDL\Http\Router\Plugin\LDL\Cache\Adapter\CacheAdapterCollection;
use LDL\Http\Router\Plugin\LDL\Cache\Adapter\CacheAdapterCollectionItem;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\CacheKeyGeneratorCollection;
use LDL\Http\Router\Plugin\LDL\Cache\Key\Generator\StaticCacheKeyGenerator;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\ParameterBag;
use LDL\Http\Router\Middleware\DispatcherRepository;
use LDL\Http\Router\Route\Config\Parser\RouteConfigParserRepository;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CachePreDispatch;
use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CachePostDispatch;
use LDL\Http\Router\Plugin\LDL\Cache\Traits\NoCacheInterfaceTrait;

class CacheDispatcherTest extends AbstractMiddleware
{
    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router = null,
        ParameterBag $urlParameters=null
    ) : ?array
    {
        return [
            'name' => $urlParameters->get('urlName')
        ];
    }
}

class TimeDispatcher extends AbstractMiddleware implements NoCacheInterface{

    use NoCacheInterfaceTrait;

    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router = null,
        ParameterBag $urlParameters = null
    ) : ?array
    {
        return [
            'date' => time()
        ];
    }
}

class RandomPreDispatcher extends AbstractMiddleware implements NoCacheInterface{

    use NoCacheInterfaceTrait;

    public function _dispatch(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router = null,
        ParameterBag $urlParameters = null
    ) : ?array
    {
        return [
            'random number' => random_int(1, 100)
        ];
    }
}

$cacheKeyGenerators = new CacheKeyGeneratorCollection();
$cacheKeyGenerators->append(new StaticCacheKeyGenerator('static.key', true))
    ->append(new RequestParameterValueCacheKeyGenerator('request.parameter.values', false));

$response = new Response();

$cacheAdapters = new CacheAdapterCollection();
$cacheAdapters->append(new CacheAdapterCollectionItem(new FilesystemAdapter(), 'fs.adapter'));

$parserCollection = new RouteConfigParserRepository();

$parserCollection->append(
    new CacheConfigParser(
        $cacheAdapters,
        $cacheKeyGenerators,
    )
);

$router = new Router(
    Request::createFromGlobals(),
    $response,
    $parserCollection
);

$dispatcherRepository = new DispatcherRepository();
$dispatcherRepository->append(new CacheDispatcherTest('test.cache.dispatcher'))
    ->append(new TimeDispatcher('test.cache.time.dispatcher'))
    ->append(new RandomPreDispatcher('test.cache.random.predispatch'))
    ->append(new CachePreDispatch('cache.predispatch'))
    ->append(new CachePostDispatch('cache.postdispatch'));

$routes = RouteFactory::fromJsonFile(
    __DIR__.'/routes.json',
    $router,
    $dispatcherRepository
);

$group = new RouteGroup('test', 'test', $routes);

$router->addGroup($group);

$router->dispatch()->send();