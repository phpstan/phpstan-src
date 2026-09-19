<?php

namespace StrShuffle;

use function PHPStan\Testing\assertType;

class X {
	const ABC = 'abcdef';

	/**
	 * @param non-empty-string $nonES
	 * @param non-falsy-string $nonFalsyString
	 * @param numeric-string $numericString
	 * @param lowercase-string $lowercaseString
	 * @param uppercase-string $uppercaseString
	 * @param uppercase-string&non-empty-string $uppercaseNonEmptyString
	 */
	function doFoo(
		string $s,
		$nonES,
		string $nonFalsyString,
		string $numericString,
		string $lowercaseString,
		string $uppercaseString,
		string $uppercaseNonEmptyString
	):void {
		assertType('lowercase-string&non-falsy-string', str_shuffle(self::ABC));
		assertType('lowercase-string&non-falsy-string', str_shuffle('abc'));
		assertType('string', str_shuffle($s));
		assertType('non-empty-string', str_shuffle($nonES));
		assertType('non-falsy-string', str_shuffle($nonFalsyString));
		assertType('non-empty-string', str_shuffle($numericString));
		assertType('lowercase-string', str_shuffle($lowercaseString));
		assertType('uppercase-string', str_shuffle($uppercaseString));
		assertType('non-empty-string&uppercase-string', str_shuffle($uppercaseNonEmptyString));
	}
}
