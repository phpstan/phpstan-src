<?php

namespace Bug10302;

use function PHPStan\Testing\assertType;

/**
 * @property-read bool $busy
 * @phpstan-require-extends Model
 */
interface BatchAware
{

}

/**
 * @property-read bool $busy
 */
class Model
{
	/**
	 * @return mixed
	 */
	public function __get(string $name)
	{
		return 1;
	}
}

class SomeModel extends Model implements BatchAware
{

}

function (BatchAware $b): void
{
	assertType('bool', $b->busy);
};
