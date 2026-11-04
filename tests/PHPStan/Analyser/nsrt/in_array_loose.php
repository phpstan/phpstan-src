<?php // lint >= 8.0

namespace InArrayLoose;

use function PHPStan\Testing\assertType;

class Foo
{
	public function looseComparison(
		string $string,
		int $int,
		float $float,
		bool $bool,
		string|int $stringOrInt,
		string|null $stringOrNull,
	): void {
		if (in_array($string, ['1', 'a'])) {
			assertType("'a'|numeric-string", $string);
		}
		if (in_array($string, [1, 'a'])) {
			assertType("'a'|numeric-string", $string);
		}
		if (in_array($int, [1, 2])) {
			assertType('1|2', $int);
		}
		if (in_array($int, ['1', 2])) {
			assertType('1|2', $int);
		}
		if (in_array($bool, [true])) {
			assertType('true', $bool);
		}
		if (in_array($bool, [true, null])) {
			assertType('bool', $bool);
		}
		if (in_array($float, [1.0, 2.0])) {
			assertType('1.0|2.0', $float);
		}
		if (in_array($float, ['1', 2.0])) {
			assertType('1.0|2.0', $float);
		}
		if (in_array($stringOrInt, ['1', '2'])) {
			assertType('1|2|numeric-string', $stringOrInt);
		}
		if (in_array($stringOrNull, ['1', 'a'])) {
			assertType("'a'|numeric-string", $stringOrNull);
		}
	}
}
