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

		assertType('lowercase-string&non-empty-string', vsprintf('%s', [$nonFalsyLowercase]));
		assertType('numeric-string', vsprintf('%d', [$nonFalsyLowercase]));
		assertType('non-falsy-string', vsprintf($format, [$nonFalsyLowercase]));
		assertType('lowercase-string&non-falsy-string', vsprintf($formatLower, [$nonFalsyLowercase]));
		assertType('lowercase-string&non-empty-string', vsprintf($nonFalsyLowercase, [$nonFalsyLowercase]));
		assertType('string', vsprintf($string, [$nonFalsyLowercase]));
	}

}
