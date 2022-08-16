<?php

namespace ClosureArgumentType;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{a: int} $array
	 */
	public function doFoo(int $integer, array $array, ?string $nullableString)
	{
		(function($context) {
			assertType('int', $context);
		})($integer);

		(function($context) {
			assertType('array{a: int}', $context);
		})($array);

		(function($context) {
			assertType('string|null', $context);
		})($nullableString);

		(function($context1, $context2, $context3) {
			assertType('int', $context1);
			assertType('array{a: int}', $context2);
			assertType('string|null', $context3);
		})($integer, $array, $nullableString);

		(function($context1, $context2, $context3 = null) {
			assertType('int', $context1);
			assertType('array{a: int}', $context2);
			assertType('mixed', $context3);
		})($integer, $array);
	}

}
