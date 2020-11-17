<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Key\Generator;

use LDL\Http\Router\Router;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestParameterNameCacheKeyGenerator extends AbstractCacheKeyGenerator
{
    /**
     * Generates a cache key which contains the name of the parameters only, not the parameter values
     *
     * @param Router $router
     * @param ParameterBag|null $urlParameters
     * @return string
     */
    public function generate(
        Router $router,
        ParameterBag $urlParameters=null
    ): string
    {
        $request = $router->getRequest();

        return implode('_', $request->getQuery()->keys());
    }
}