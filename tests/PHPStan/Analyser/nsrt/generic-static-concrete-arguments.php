<?php declare(strict_types = 1);

namespace GenericStaticConcreteArguments;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Box
{

	/** @return static<int, string> */
	public function concrete()
	{
		return new static();
	}

	/** @return static<int, TValue> */
	public function withTemplate()
	{
		return new static();
	}

	/** @return array<TKey, TValue> */
	public function all(): array
	{
		return [];
	}

	/** @return TValue */
	public function first()
	{
		return 1;
	}

}

/**
 * @param Box<string, bool> $box
 */
function test(Box $box): void
{
	assertType('GenericStaticConcreteArguments\Box<int, string>', $box->concrete());
	assertType('array<int, string>', $box->concrete()->all());
	assertType('string', $box->concrete()->first());
	assertType('array<int, bool>', $box->withTemplate()->all());
}
