<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Key\Generator;

use LDL\Http\Router\Router;
use Symfony\Component\HttpFoundation\ParameterBag;

class StaticCacheKeyGenerator extends AbstractCacheKeyGenerator
{

    public function generate(
        Router $router,
        ParameterBag $urlParameters=null
    ): string
    {
        return $this->getName();
    }
}