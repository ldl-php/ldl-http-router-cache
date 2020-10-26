<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Key\Generator;

use LDL\Http\Router\Route\RouteInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

interface CacheKeyGeneratorInterface {

    /**
     * @return string
     */
    public function getName() : string;

    /**
     * @param RouteInterface $route
     * @param ParameterBag $urlParameters
     * @return string
     */
    public function generate(RouteInterface $route, ParameterBag $urlParameters) : string;

    /**
     * @return bool
     */
    public function isDefault() : bool;
}
