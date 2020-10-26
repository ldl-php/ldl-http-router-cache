<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Adapter;

use Symfony\Component\Cache\Adapter\AdapterInterface;

interface CacheAdapterCollectionItemInterface
{

    /**
     * @return AdapterInterface
     */
    public function getAdapter() : AdapterInterface;

    /**
     * @return string
     */
    public function getName() : string;

}