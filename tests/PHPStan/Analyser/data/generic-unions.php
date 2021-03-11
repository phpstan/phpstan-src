<?php

namespace GenericUnions;

use function PHPStan\Analyser\assertType;

class Foo
{

	/**
	 * @template T
	 * @param T|null $p
	 * @return T
	 */
	public function doFoo($p)
	{
		if ($p === null) {
			throw new \Exception();
		}

		return $p;
	}

	/**
	 * @template T
	 * @param T $p
	 * @return T
	 */
	public function doBar($p)
	{
		return $p;
	}

	/**
	 * @template T
	 * @param T|int|float $p
	 * @return T
	 */
	public function doBaz($p)
	{
		return $p;
	}

	/**
	 * @param int|string $stringOrInt
	 */
	public function foo(
		?string $nullableString,
		$stringOrInt
	): void
	{
		assertType('string', $this->doFoo($nullableString));
		assertType('int|string', $this->doFoo($stringOrInt));

		assertType('string|null', $this->doBar($nullableString));

		assertType('int', $this->doBaz(1));
		assertType('string', $this->doBaz('foo'));
		assertType('float', $this->doBaz(1.2));
		assertType('string', $this->doBaz($stringOrInt));
	}

}
