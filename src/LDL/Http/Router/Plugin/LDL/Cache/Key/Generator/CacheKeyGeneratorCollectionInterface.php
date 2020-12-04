<?php declare(strict_types=1);

namespace LDL\Http\Router\Plugin\LDL\Cache\Key\Generator;

use LDL\Type\Collection\Interfaces\Validation\HasKeyValidatorChainInterface;
use LDL\Type\Collection\Interfaces\Selection\SingleSelectionInterface;
use LDL\Type\Collection\Interfaces\Validation\HasValueValidatorChainInterface;

interface CacheKeyGeneratorCollectionInterface extends HasKeyValidatorChainInterface, SingleSelectionInterface, HasValueValidatorChainInterface
{


}
