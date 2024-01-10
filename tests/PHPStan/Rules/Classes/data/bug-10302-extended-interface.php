<?php

namespace Bug10302ExtendedInterface;

use function PHPStan\Testing\assertType;

/**
 * @property-read bool $busy
 * @phpstan-require-extends Model
 */
interface BatchAware
{
}

interface BatchAwareExtended extends BatchAware
{

}

/**
 * @property-read bool $busy2
 */
class Model implements BatchAware
{
	/**
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return 1;
	}
}

class AnotherModel implements BatchAwareExtended
{

}


/**
 * @property-read bool $busy
 * @phpstan-require-extends TraitModel
 */
trait BatchAwareTrait
{
}

class TraitModel {
	use BatchAwareTrait;
}
