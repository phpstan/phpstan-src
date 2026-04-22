<?php declare(strict_types = 1);

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

}
