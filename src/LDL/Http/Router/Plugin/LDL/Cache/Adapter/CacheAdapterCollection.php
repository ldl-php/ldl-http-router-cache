<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Adapter;

use LDL\Type\Collection\Interfaces;
use LDL\Type\Collection\Traits\Selection\SingleSelectionTrait;
use LDL\Type\Collection\Traits\Validator\KeyValidatorChainTrait;
use LDL\Type\Collection\Types\Object\ObjectCollection;
use LDL\Type\Collection\Types\Object\Validator\InterfaceComplianceItemValidator;
use LDL\Type\Collection\Validator\UniqueValidator;

class CacheAdapterCollection extends ObjectCollection implements CacheAdapterCollectionInterface
{
    use SingleSelectionTrait;
    use KeyValidatorChainTrait;

    public function __construct(iterable $items = null)
    {
        parent::__construct($items);

        $this->getValueValidatorChain()
            ->append(new InterfaceComplianceItemValidator(CacheAdapterCollectionItemInterface::class))
            ->lock();

        $this->getKeyValidatorChain()
            ->append(new UniqueValidator())
            ->lock();

    }

    /**
     * @param CacheAdapterCollectionItemInterface $item
     * @param null $key
     * @return Interfaces\CollectionInterface
     * @throws \Exception
     */
    public function append($item, $key = null): Interfaces\CollectionInterface
    {
        return parent::append($item, $item->getName());
    }

}
