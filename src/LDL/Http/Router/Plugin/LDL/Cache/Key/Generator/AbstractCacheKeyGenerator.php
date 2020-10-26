<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Key\Generator;

abstract class AbstractCacheKeyGenerator implements CacheKeyGeneratorInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isDefault;

    public function __construct(string $name, bool $isDefault=false)
    {
        $this->name = $name;
        $this->isDefault = $isDefault;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function isDefault() : bool
    {
        return $this->isDefault;
    }
}
