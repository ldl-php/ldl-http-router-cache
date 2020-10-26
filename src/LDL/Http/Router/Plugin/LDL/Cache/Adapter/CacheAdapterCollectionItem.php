<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Adapter;


use Symfony\Component\Cache\Adapter\AdapterInterface;

class CacheAdapterCollectionItem implements CacheAdapterCollectionItemInterface
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var string
     */
    private $name;

    public function __construct(AdapterInterface $adapter, string $name)
    {
        $this->adapter = $adapter;
        $this->name = $name;
    }

    public function getAdapter() : AdapterInterface
    {
        return $this->adapter;
    }

    public function getName() : string
    {
        return $this->name;
    }
}