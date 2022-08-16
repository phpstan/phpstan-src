<?php

namespace ArrowFunctionArgumentType;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{a: int} $array
	 */
	public function doFoo(int $integer, array $array, ?string $nullableString)
	{
		(fn($context) => assertType('int', $context))($integer);

		(fn($context) => assertType('array{a: int}', $context))($array);

		(fn($context) => assertType('string|null', $context))($nullableString);

		(fn($a, $b, $c) => assertType('array{int, array{a: int}, string|null}', [$a, $b, $c]))($integer, $array, $nullableString);

		(fn($a, $b, $c = null) => assertType('array{int, array{a: int}, mixed}', [$a, $b, $c]))($integer, $array);
	}

}
