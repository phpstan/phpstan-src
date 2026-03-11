<?php // lint >= 8.4

namespace ArrayAll;

use DateTime;
use DateTimeImmutable;

use function PHPStan\Testing\assertType;

class Foo {

	/**
	 * @param array<mixed> $array
	 */
	public function test1($array) {
		if (array_all($array, fn ($value) => is_int($value))) {
			assertType("array<int>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test2($array) {
		if (array_all($array, fn ($value, $key) => is_string($key))) {
			assertType("array<string, mixed>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test3($array) {
		if (array_all($array, fn ($value, $key) => is_string($key) && is_int($value))) {
			assertType("array<string, int>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test4($array) {
		if (array_all($array, fn ($value) => is_string($value) && is_numeric($value))) {
			assertType("array<numeric-string>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test5($array) {
		if (array_all($array, fn ($value) => is_bool($value) || is_float($value))) {
			assertType("array<bool|float>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test6($array) {
		if (array_all($array, fn ($value) => is_float(1))) {
			assertType("array<mixed>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test7($array) {
		if (array_all($array, fn ($value) => $value instanceof DateTime)) {
			assertType("array<DateTime>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test8($array) {
		if (array_all($array, fn ($value) => $value instanceof DateTime || $value instanceof DateTimeImmutable)) {
			assertType("array<DateTime|DateTimeImmutable>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param list<mixed> $array
	 */
	public function test9($array) {
		if (array_all($array, fn ($value, $key) => is_int($key))) {
			assertType("list<mixed>", $array);
		} else {
			assertType("list<mixed>", $array);
		}
		assertType("list<mixed>", $array);
	}

	/**
	 * @param non-empty-array<mixed> $array
	 */
	public function test10($array) {
		if (array_all($array, fn ($value, $key) => is_int($key))) {
			assertType("non-empty-array<int, mixed>", $array);
		} else {
			assertType("non-empty-array<mixed>", $array);
		}
		assertType("non-empty-array", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test11($array) {
		if (array_all($array, function ($value) {return is_int($value);})) {
			assertType("array<int>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test12($array) {
		if (array_all($array, function ($value) {$value = 1; return is_int($value);})) {
			assertType("array<mixed>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test13($array) {
		if (array_all($array, function ($value, $key) {return is_int($value) && is_string($key);})) {
			assertType("array<string, int>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test14($array) {
		if (array_all($array, function ($value, $key) {return;})) {
			assertType("array<mixed>", $array);
		} else {
			assertType("array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}
}
