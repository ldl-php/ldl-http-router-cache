<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Key\Generator;

use LDL\Http\Router\Router;
use Symfony\Component\HttpFoundation\ParameterBag;

interface CacheKeyGeneratorInterface {

    /**
     * @return string
     */
    public function getName() : string;

    /**
     * @param Router $router
     * @param ParameterBag $urlParameters
     * @return string
     */
    public function generate(
        Router $router,
        ParameterBag $urlParameters=null
    ) : string;

    /**
     * @return bool
     */
    public function isDefault() : bool;

    /**
     * @param array|null $options
     * @return CacheKeyGeneratorInterface
     */
    public function setOptions(?array $options) : CacheKeyGeneratorInterface;

    /**
     * @return array|null
     */
    public function getOptions() : ?array;
}
