<?php

namespace LowercaseStringSprintf;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param lowercase-string $lowercase
	 * @param lowercase-string&non-empty-string $nonEmptyLowercase
	 * @param lowercase-string&non-falsy-string $nonFalsyLowercase
	 */
	public function doSprintf(
		string $string,
		string $lowercase,
		string $nonEmptyLowercase,
		string $nonFalsyLowercase,
		bool $bool
	): void {
		$format = $bool ? 'Foo 1 %s' : 'Foo 2 %s';
		$formatLower = $bool ? 'foo 1 %s' : 'foo 2 %s';
		$constant = $bool ? 'A' : 'B';
		$constantLower = $bool ? 'a' : 'b';

		assertType("'A'|'B'", sprintf('%s', $constant));
		assertType("'0'", sprintf('%d', $constant));
		assertType("'Foo 1 A'|'Foo 1 B'|'Foo 2 A'|'Foo 2 B'", sprintf($format, $constant));
		assertType("'foo 1 A'|'foo 1 B'|'foo 2 A'|'foo 2 B'", sprintf($formatLower, $constant));
		assertType('string', sprintf($lowercase, $constant));
		assertType('string', sprintf($string, $constant));

		assertType("'a'|'b'", sprintf('%s', $constantLower));
		assertType("'0'", sprintf('%d', $constantLower));
		assertType("'Foo 1 a'|'Foo 1 b'|'Foo 2 a'|'Foo 2 b'", sprintf($format, $constantLower));
		assertType("'foo 1 a'|'foo 1 b'|'foo 2 a'|'foo 2 b'", sprintf($formatLower, $constantLower));
		assertType('lowercase-string', sprintf($lowercase, $constantLower));
		assertType('string', sprintf($string, $constantLower));

		assertType('lowercase-string', sprintf('%s', $lowercase));
		assertType('numeric-string', sprintf('%d', $lowercase));
		assertType('non-falsy-string', sprintf($format, $lowercase));
		assertType('lowercase-string&non-falsy-string', sprintf($formatLower, $lowercase));
		assertType('lowercase-string', sprintf($lowercase, $lowercase));
		assertType('string', sprintf($string, $lowercase));

		assertType('lowercase-string&non-empty-string', sprintf('%s', $nonEmptyLowercase));
		assertType('numeric-string', sprintf('%d', $nonEmptyLowercase));
		assertType('non-falsy-string', sprintf($format, $nonEmptyLowercase));
		assertType('lowercase-string&non-falsy-string', sprintf($formatLower, $nonEmptyLowercase));
		assertType('lowercase-string&non-empty-string', sprintf($nonEmptyLowercase, $nonEmptyLowercase));
		assertType('string', sprintf($string, $nonEmptyLowercase));

		assertType('lowercase-string&non-falsy-string', sprintf('%s', $nonFalsyLowercase));
		assertType('numeric-string', sprintf('%d', $nonFalsyLowercase));
		assertType('non-falsy-string', sprintf($format, $nonFalsyLowercase));
		assertType('lowercase-string&non-falsy-string', sprintf($formatLower, $nonFalsyLowercase));
		assertType('lowercase-string&non-empty-string', sprintf($nonFalsyLowercase, $nonFalsyLowercase));
		assertType('string', sprintf($string, $nonFalsyLowercase));
	}

	/**
	 * @param lowercase-string $lowercase
	 * @param lowercase-string&non-empty-string $nonEmptyLowercase
	 * @param lowercase-string&non-falsy-string $nonFalsyLowercase
	 */
	public function doVSprintf(
		string $string,
		string $lowercase,
		string $nonEmptyLowercase,
		string $nonFalsyLowercase,
		bool $bool
	): void {
		$format = $bool ? 'Foo 1 %s' : 'Foo 2 %s';
		$formatLower = $bool ? 'foo 1 %s' : 'foo 2 %s';
		$constant = $bool ? 'A' : 'B';
		$constantLower = $bool ? 'a' : 'b';

		assertType("'A'|'B'", vsprintf('%s', [$constant]));
		assertType('numeric-string', vsprintf('%d', [$constant]));
		assertType('non-falsy-string', vsprintf($format, [$constant]));
		assertType('non-falsy-string', vsprintf($formatLower, [$constant]));
		assertType('string', vsprintf($lowercase, [$constant]));
		assertType('string', vsprintf($string, [$constant]));

		assertType("'a'|'b'", vsprintf('%s', [$constantLower]));
		assertType('numeric-string', vsprintf('%d', [$constantLower]));
		assertType('non-falsy-string', vsprintf($format, [$constantLower]));
		assertType('lowercase-string&non-falsy-string', vsprintf($formatLower, [$constantLower]));
		assertType('lowercase-string', vsprintf($lowercase, [$constantLower]));
		assertType('string', vsprintf($string, [$constantLower]));

		assertType('lowercase-string', vsprintf('%s', [$lowercase]));
		assertType('numeric-string', vsprintf('%d', [$lowercase]));
		assertType('non-falsy-string', vsprintf($format, [$lowercase]));
		assertType('lowercase-string&non-falsy-string', vsprintf($formatLower, [$lowercase]));
		assertType('lowercase-string', vsprintf($lowercase, [$lowercase]));
		assertType('string', vsprintf($string, [$lowercase]));

		assertType('lowercase-string&non-empty-string', vsprintf('%s', [$nonEmptyLowercase]));
		assertType('numeric-string', vsprintf('%d', [$nonEmptyLowercase]));
		assertType('non-falsy-string', vsprintf($format, [$nonEmptyLowercase]));
		assertType('lowercase-string&non-falsy-string', vsprintf($formatLower, [$nonEmptyLowercase]));
		assertType('lowercase-string&non-empty-string', vsprintf($nonEmptyLowercase, [$nonEmptyLowercase]));
		assertType('string', vsprintf($string, [$nonEmptyLowercase]));

		assertType('lowercase-string&non-falsy-string', vsprintf('%s', [$nonFalsyLowercase]));
		assertType('numeric-string', vsprintf('%d', [$nonFalsyLowercase]));
		assertType('non-falsy-string', vsprintf($format, [$nonFalsyLowercase]));
		assertType('lowercase-string&non-falsy-string', vsprintf($formatLower, [$nonFalsyLowercase]));
		assertType('lowercase-string&non-empty-string', vsprintf($nonFalsyLowercase, [$nonFalsyLowercase]));
		assertType('string', vsprintf($string, [$nonFalsyLowercase]));
	}

}
