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
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test3($array) {
		if (array_all($array, fn ($value, $key) => is_string($key) && is_int($value))) {
			assertType("array<string, int>", $array);
		} else {
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test6($array) {
		if (array_all($array, fn ($value) => is_float(1))) {
			// the predicate can never be true, so array_all only holds for the empty array
			assertType("array{}", $array);
		} else {
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-list<mixed>", $array);
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
		assertType("non-empty-array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test11($array) {
		if (array_all($array, function ($value) {return is_int($value);})) {
			assertType("array<int>", $array);
		} else {
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-array<mixed>", $array);
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
			assertType("non-empty-array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test15($array) {
		if (array_all($array, fn ($value) => is_int($value)) === true) {
			assertType("array<int>", $array);
		} else {
			assertType("non-empty-array<mixed>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * A negated predicate narrows via sureNotTypes - the previous
	 * implementation could not express this.
	 *
	 * @param array<mixed> $array
	 */
	public function testNegatedIsNull($array) {
		if (array_all($array, fn ($value) => !is_null($value))) {
			assertType("array<mixed~null>", $array);
		}
	}

	/**
	 * @param array<mixed> $array
	 */
	public function testNotIdenticalNull($array) {
		if (array_all($array, fn ($value) => $value !== null)) {
			assertType("array<mixed~null>", $array);
		}
	}

	/**
	 * Bare truthiness of the value.
	 *
	 * @param array<mixed> $array
	 */
	public function testBareTruthiness($array) {
		if (array_all($array, fn ($value) => $value)) {
			assertType("array<mixed~(0|0.0|''|'0'|array{}|false|null)>", $array);
		}
	}

	/**
	 * A possibly-empty list stays sound: the empty list makes array_all true,
	 * so the narrowed type is array{}, not never.
	 *
	 * @param list<mixed> $array
	 */
	public function testListStringKey($array) {
		if (array_all($array, fn ($value, $key) => is_string($key))) {
			assertType("array{}", $array);
		} else {
			assertType("non-empty-list<mixed>", $array);
		}
	}

	/**
	 * First-class callable with a two-parameter predicate.
	 *
	 * @param array<mixed> $array
	 */
	public function testFirstClassCallable($array) {
		if (array_all($array, self::isIntValue(...))) {
			assertType("array<int>", $array);
		}
	}

	/**
	 * Constant-string callable naming a two-parameter user function.
	 *
	 * @param array<mixed> $array
	 */
	public function testStringCallable($array) {
		if (array_all($array, '\ArrayAll\isIntValue')) {
			assertType("array<int>", $array);
		}
	}

	/**
	 * @phpstan-assert-if-true int $value
	 */
	public static function isIntValue(mixed $value, int|string $key): bool {
		return is_int($value);
	}

	/**
	 * A predicate using @phpstan-assert-if-true through a static method call.
	 *
	 * @param array<mixed> $array
	 */
	public function testAssertIfTrue($array) {
		if (array_all($array, fn ($value) => self::isDateTime($value))) {
			assertType("array<DateTime>", $array);
		}
	}

	/**
	 * @phpstan-assert-if-true DateTime $value
	 */
	public static function isDateTime(mixed $value): bool {
		return $value instanceof DateTime;
	}

	/**
	 * Array shapes are narrowed per offset: the required int offsets stay, the
	 * optional string offset that cannot be int is dropped.
	 *
	 * @param array{a: int, b?: string, c: int} $array
	 */
	public function testShape($array) {
		if (array_all($array, fn ($value) => is_int($value))) {
			assertType("array{a: int, c: int}", $array);
		}
	}

	/**
	 * A by-ref callback parameter disables narrowing (the callback may rewrite
	 * elements).
	 *
	 * @param array<mixed> $array
	 */
	public function testByRefParameter($array) {
		if (array_all($array, function (&$value) { return is_int($value); })) {
			assertType("array<mixed>", $array);
		}
	}

}

/**
 * @phpstan-assert-if-true int $value
 */
function isIntValue(mixed $value, int|string $key): bool {
	return is_int($value);
}
