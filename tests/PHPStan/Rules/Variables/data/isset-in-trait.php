<?php declare(strict_types = 1);

namespace IssetInTrait;

/**
 * The property is only declared in one of the classes using the trait,
 * so nothing should be reported.
 */
trait MaybeDeclaredPropertyTrait
{

	public function doFoo(): void
	{
		var_dump($this->i ?? -1);
		var_dump(isset($this->i));
		var_dump(empty($this->i));
	}

}

class DeclaredProperty
{

	use MaybeDeclaredPropertyTrait;

	/** @var positive-int */
	public int $i = 10;

}

class UndeclaredProperty
{

	use MaybeDeclaredPropertyTrait;

}

/**
 * The property is non-nullable in one class using the trait and nullable in the other,
 * so nothing should be reported.
 */
trait DifferentPropertyTypeTrait
{

	public function doFoo(): void
	{
		var_dump($this->j ?? -1);
		var_dump(isset($this->j));
		var_dump(empty($this->j));
	}

}

class NonNullableProperty
{

	use DifferentPropertyTypeTrait;

	/** @var positive-int */
	public int $j = 10;

}

class NullableProperty
{

	use DifferentPropertyTypeTrait;

	public ?int $j = null;

}

/**
 * The property is non-nullable in every class using the trait, so the errors are reported
 * in the context of each of them.
 */
trait SamePropertyTypeTrait
{

	public function doFoo(): void
	{
		var_dump($this->k ?? -1);
		var_dump(isset($this->k));
		var_dump(empty($this->k));
	}

}

class FirstNonNullableProperty
{

	use SamePropertyTypeTrait;

	/** @var positive-int */
	public int $k = 10;

}

class SecondNonNullableProperty
{

	use SamePropertyTypeTrait;

	/** @var positive-int */
	public int $k = 20;

}

/**
 * The checked expression does not depend on the class using the trait, so the errors are
 * reported once, directly in the trait.
 */
trait ClassIndependentTrait
{

	public function doFoo(): void
	{
		$s = 'foo';
		var_dump($s ?? -1);
		var_dump(isset($s));
		var_dump(empty($s));
	}

}

class FirstClassIndependent
{

	use ClassIndependentTrait;

}

class SecondClassIndependent
{

	use ClassIndependentTrait;

}
