<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Key\Generator;

use LDL\Http\Router\Route\RouteInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class StaticCacheKeyGenerator extends AbstractCacheKeyGenerator
{
    public function __construct(
        string $name,
        bool $isDefault = false
    )
    {
        parent::__construct($name, $isDefault);
    }

    public function generate(
        RouteInterface $route,
        ParameterBag $urlParameters
    ): string
    {
        return $this->getName();
    }
}