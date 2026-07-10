<?php // lint >= 8.4

namespace ArrayAny;

use DateTime;

use function PHPStan\Testing\assertType;

class Foo {

	/**
	 * array_any true means at least one element matches (so the array is
	 * non-empty); false means no element matches, narrowing every element by
	 * the predicate being falsey.
	 *
	 * @param array<mixed> $array
	 */
	public function test1($array) {
		if (array_any($array, fn ($value) => $value === null)) {
			assertType("non-empty-array<mixed>", $array);
		} else {
			assertType("array<mixed~null>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function test2($array) {
		if (array_any($array, fn ($value) => is_int($value))) {
			assertType("non-empty-array<mixed>", $array);
		} else {
			assertType("array<mixed~int>", $array);
		}
		assertType("array<mixed>", $array);
	}

	/**
	 * The dual of `!is_null` for array_all: `!array_any(... === null)` narrows
	 * away null.
	 *
	 * @param array<mixed> $array
	 */
	public function testNegated($array) {
		if (!array_any($array, fn ($value) => $value === null)) {
			assertType("array<mixed~null>", $array);
		}
	}

	/**
	 * @param array<mixed> $array
	 */
	public function testKey($array) {
		if (array_any($array, fn ($value, $key) => is_string($key))) {
			assertType("non-empty-array<mixed>", $array);
		} else {
			assertType("array<int, mixed>", $array);
		}
	}

	/**
	 * @param list<mixed> $array
	 */
	public function testList($array) {
		if (array_any($array, fn ($value, $key) => is_string($key))) {
			assertType("non-empty-list<mixed>", $array);
		} else {
			assertType("list<mixed>", $array);
		}
	}

	/**
	 * @param non-empty-array<mixed> $array
	 */
	public function testNonEmpty($array) {
		if (array_any($array, fn ($value) => is_int($value))) {
			assertType("non-empty-array<mixed>", $array);
		} else {
			assertType("non-empty-array<mixed~int>", $array);
		}
	}

	/**
	 * First-class callable predicate, narrowing on the falsey side.
	 *
	 * @param array<int|string> $array
	 */
	public function testFirstClassCallable($array) {
		if (!array_any($array, self::isIntValue(...))) {
			assertType("array<string>", $array);
		}
	}

	/**
	 * Constant-string callable predicate, narrowing on the falsey side.
	 *
	 * @param array<int|string> $array
	 */
	public function testStringCallable($array) {
		if (!array_any($array, '\ArrayAny\isIntValue')) {
			assertType("array<string>", $array);
		}
	}

	/**
	 * @phpstan-assert-if-true int $value
	 */
	public static function isIntValue(mixed $value, int|string $key): bool {
		return is_int($value);
	}

	/**
	 * @param array<mixed> $array
	 */
	public function testAssertIfTrue($array) {
		if (!array_any($array, fn ($value) => $value instanceof DateTime)) {
			assertType("array<mixed~DateTime>", $array);
		}
	}

	/**
	 * Optional offsets that would match are dropped; required offsets that
	 * cannot fail make the whole shape impossible.
	 *
	 * @param array{a?: int, b: string} $array
	 */
	public function testShapeOptionalDropped($array) {
		if (!array_any($array, fn ($value) => is_int($value))) {
			assertType("array{b: string}", $array);
		}
	}

	/**
	 * @param array{a: int, b: string} $array
	 */
	public function testShapeRequiredImpossible($array) {
		if (!array_any($array, fn ($value) => is_int($value))) {
			assertType("*NEVER*", $array);
		}
	}

}

/**
 * @phpstan-assert-if-true int $value
 */
function isIntValue(mixed $value, int|string $key): bool {
	return is_int($value);
}
