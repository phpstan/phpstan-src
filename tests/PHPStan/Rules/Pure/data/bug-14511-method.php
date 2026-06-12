<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug14511Method;

class Foo
{

	/**
	 * @phpstan-pure
	 * @template T of mixed
	 * @param T $val
	 */
	public function testStringCast(mixed $val): ?string
	{
		if (is_int($val)) {
			return (string) $val;
		}
		return null;
	}

	/**
	 * @phpstan-pure
	 * @template T of mixed
	 * @param T $val
	 */
	public function testStringConcat(mixed $val): ?string
	{
		if (is_int($val)) {
			return 'value: ' . $val;
		}
		return null;
	}

	/**
	 * @phpstan-pure
	 * @template T of mixed
	 * @param T $val
	 */
	public function testEmptyNonArray(mixed $val): ?string
	{
		if (empty($val) && !\is_array($val)) {
			return (string) $val;
		}
		return null;
	}

}
