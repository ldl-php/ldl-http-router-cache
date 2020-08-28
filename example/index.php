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
use LDL\Http\Router\Route\Parameter\ParameterCollection;
use LDL\Http\Router\Route\Parameter\ParameterConverterInterface;
use LDL\Http\Router\Route\Parameter\ParameterInterface;
use LDL\Http\Router\Route\RouteInterface;
use LDL\Http\Router\Router;
use LDL\Http\Router\Schema\SchemaRepository;

use LDL\Http\Router\Plugin\LDL\Cache\Dispatcher\CacheableInterface;
use LDL\Http\Router\Plugin\LDL\Cache\Config\ConfigParser;

class Dispatch implements RouteDispatcherInterface, CacheableInterface
{
    public function __construct()
    {
    }

    public function getCacheKey(
        RouteInterface $route,
        RequestInterface $request,
        ResponseInterface $response
    ): string
    {
        return 'test2';
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

class NameTransformer implements ParameterConverterInterface{
    public function convert(ParameterInterface $parameter)
    {
        return strtoupper($parameter->getValue());
    }
}

$parserCollection = new RouteConfigParserCollection();
$parserCollection->append(new ConfigParser());

$schemaRepo = new SchemaRepository();

$schemaRepo->append(__DIR__.'/schema/header-schema.json', 'header-parameters.schema');
$schemaRepo->append(__DIR__.'/schema/parameter-schema.json', 'request-parameters.schema');
$schemaRepo->append(__DIR__.'/schema/url-parameters-schema.json', 'url-parameters.schema');

try{
    $routes = RouteFactory::fromJsonFile(
        __DIR__.'/routes.json',
        null,
        $schemaRepo,
        $parserCollection
    );
}catch(\Exception $e){
    var_dump($e->getMessage());
    die("jej");
}

$group = new RouteGroup('student', 'student', $routes);

$response = new Response();

$router = new Router(
    Request::createFromGlobals(),
    $response
);

$router->addGroup($group);

$router->dispatch()->send();