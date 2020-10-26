<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Adapter;

use LDL\Type\Collection\Interfaces\CollectionInterface;
use LDL\Type\Collection\Interfaces\Selection\SingleSelectionInterface;
use LDL\Type\Collection\Interfaces\Validation\HasKeyValidatorChainInterface;

interface CacheAdapterCollectionInterface extends CollectionInterface, SingleSelectionInterface, HasKeyValidatorChainInterface
{

}
