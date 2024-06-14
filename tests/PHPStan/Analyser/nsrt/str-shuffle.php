<?php

namespace StrShuffle;

use function PHPStan\Testing\assertType;

class X {
	const ABC = 'abcdef';

	/**
	 * @param non-empty-string $nonES
	 */
	function doFoo(string $s, $nonES):void {
		assertType('non-empty-string', str_shuffle(self::ABC));
		assertType('non-empty-string', str_shuffle('abc'));
		assertType('string', str_shuffle($s));
		assertType('non-empty-string', str_shuffle($nonES));
	}
}
