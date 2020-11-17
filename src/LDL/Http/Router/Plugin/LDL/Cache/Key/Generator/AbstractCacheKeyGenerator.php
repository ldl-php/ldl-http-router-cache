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

    /**
     * @var array|null
     */
    private $options;

    public function __construct(
        string $name,
        bool $isDefault=false,
        array $options=null
    )
    {
        $this->name = $name;
        $this->isDefault = $isDefault;
        $this->setOptions($options);
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function isDefault() : bool
    {
        return $this->isDefault;
    }

    public function getOptions() : ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options) : CacheKeyGeneratorInterface
    {
        $this->options = $options;
        return $this;
    }
}
