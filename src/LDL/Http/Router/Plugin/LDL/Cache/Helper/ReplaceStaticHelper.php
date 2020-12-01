<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Helper;

use LDL\Http\Router\Plugin\LDL\Cache\Interfaces\NoCacheInterface;
use LDL\Http\Router\Response\Parser\ResponseParserInterface;
use LDL\Http\Router\Router;
use LDL\Http\Core\Request\RequestInterface;
use LDL\Http\Core\Response\ResponseInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class ReplaceStaticHelper
{
    public static function replace(
        RequestInterface $request,
        ResponseInterface $response,
        Router $router,
        ResponseParserInterface $responseParser,
        array &$data,
        ParameterBag $urlParameters = null
    ) : void
    {
        $currentRoute = $router->getCurrentRoute();

        $chains = [
            $currentRoute->getPreDispatchChain(),
            $currentRoute->getDispatchChain(),
            $currentRoute->getPostDispatchChain()
        ];

        foreach($chains as $chain){
            foreach($chain as $dispatcher){
                if(!($dispatcher instanceof NoCacheInterface)){
                    continue;
                }

                $dispatcherResult = $dispatcher->_dispatch(
                    $request,
                    $response,
                    $router,
                    $urlParameters
                );

                $responseParser->parse($router, $dispatcherResult);

                $data['data']['body'] = str_replace(
                    sprintf('"%s"', $dispatcher->getStaticResult()),
                    $responseParser->getResult(),
                    $data['data']['body']
                );
            }
        }
    }
}