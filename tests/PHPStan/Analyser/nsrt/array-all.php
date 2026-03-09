<?php // lint >= 8.4

namespace ArrayAll;

use function PHPStan\Testing\assertType;

class Foo {
	/**
	 * @param array<mixed> $array
	 */
	public function test1($array) {
		if (array_all($array, fn ($value) => is_int($value))) {
			assertType("array<int>", $array);
		}
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test2($array) {
		if (array_all($array, fn ($value, $key) => is_string($key))) {
			assertType("array<string, mixed>", $array);
		}
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test3($array) {
		if (array_all($array, fn ($value, $key) => is_string($key) && is_int($value))) {
			assertType("array<string, int>", $array);
		}
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test4($array) {
		if (array_all($array, fn ($value) => is_string($value) && is_numeric($value))) {
			assertType("array<numeric-string>", $array);
		}
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test5($array) {
		if (array_all($array, fn ($value) => is_bool($value) || is_float($value))) {
			assertType("array<bool|float>", $array);
		}
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test6($array) {
		if (array_all($array, fn ($value) => is_float(1))) {
			assertType("array<mixed>", $array);
		}
	}
}
