<?php declare(strict_types=1);

namespace NarrowTaggedUnion;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param array{string, '', non-empty-string}|array{string, numeric-string} $arr */
	public function sayHello(array $arr): void
	{
		if (count($arr) === 1) {
			assertType('*NEVER*', $arr);
		} else {
			assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr) === 2) {
			assertType('array{string, numeric-string}', $arr);
		} else {
			assertType("array{string, '', non-empty-string}", $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr) === 3) {
			assertType("array{string, '', non-empty-string}", $arr);
		} else {
			assertType('array{string, numeric-string}', $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr, COUNT_NORMAL) === 3) {
			assertType("array{string, '', non-empty-string}", $arr);
		} else {
			assertType('array{string, numeric-string}', $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr, COUNT_RECURSIVE) === 3) {
			assertType("array{string, '', non-empty-string}", $arr);
		} else {
			assertType('array{string, numeric-string}', $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

		if (count($arr) === 4) {
			assertType('*NEVER*', $arr);
		} else {
			assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);
		}
		assertType("array{string, '', non-empty-string}|array{string, numeric-string}", $arr);

	}
}

