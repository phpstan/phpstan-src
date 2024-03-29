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
 * @property-read bool $busy2
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

function (BatchAware $b): void
{
	assertType('bool', $b->busy);
	assertType('bool', $b->busy2);
};

class ModelWithoutAllowDynamicProperties
{

}

/**
 * @property-read bool $busy
 * @phpstan-require-extends ModelWithoutAllowDynamicProperties
 */
interface BatchAwareWithoutAllowDynamicProperties
{

}

function (BatchAwareWithoutAllowDynamicProperties $b): void
{
	$result = $b->busy; // @phpstan-ignore-line

	assertType('*ERROR*', $result);
};
