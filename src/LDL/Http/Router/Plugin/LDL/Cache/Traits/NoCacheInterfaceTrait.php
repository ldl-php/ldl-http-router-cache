<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Traits;

trait NoCacheInterfaceTrait
{
    public function getStaticResult() : string
    {
        return sprintf('CachePlaceHolder_%s', md5(__CLASS__));
    }
}