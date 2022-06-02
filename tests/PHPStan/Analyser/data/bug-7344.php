<?php

namespace Bug7344;

use Exception;
use function PHPStan\Testing\assertType;

interface PhpdocTypeInterface
{
	/**
	 * Interface for phpdoc type for Phpstan only
	 *
	 * @return never
	 */
	public function neverImplement(): void;
}

interface IsEntity extends PhpdocTypeInterface
{
}

class Model
{

	public function getModel(bool $allowOnModel = false): self
	{
		return $this;
	}
}

class Person extends Model
{
}

class Female extends Person
{
}

class Male extends Person
{
}

class Foo
{

	/** @param Male|Female $maleOrFemale */
	public function doFoo($maleOrFemale): void
	{
		if (!$maleOrFemale instanceof IsEntity) {
			return;
		}

		assertType('(Bug7344\Female&Bug7344\IsEntity)|(Bug7344\IsEntity&Bug7344\Male)', $maleOrFemale);
		assertType('int', $maleOrFemale->getModel());
	}
}
