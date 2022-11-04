<?php

namespace Strtr;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $nonEmptyString
 * @param non-falsy-string $nonFalseyString
 */
function doFoo(string $s, $nonEmptyString, $nonFalseyString) {
	assertType('string', strtr($s, 'f', 'b'));
	assertType('string', strtr($s, ['f' => 'b']));
	assertType('string', strtr($s, ['f' => 'b', 'o' => 'a']));

	assertType('string', strtr($s, $s, $nonEmptyString));
	assertType('string', strtr($s, $nonEmptyString, $nonEmptyString));
	assertType('string', strtr($s, $nonFalseyString, $nonFalseyString));

	assertType('non-empty-string', strtr($nonEmptyString, $s, $nonEmptyString));
	assertType('non-empty-string', strtr($nonEmptyString, $nonEmptyString, $nonEmptyString));
	assertType('non-empty-string', strtr($nonEmptyString, $nonFalseyString, $nonFalseyString));

	assertType('non-empty-string', strtr($nonFalseyString, $s, $nonEmptyString));
	assertType('non-falsy-string', strtr($nonFalseyString, $nonEmptyString, $nonFalseyString));
	assertType('non-falsy-string', strtr($nonFalseyString, $nonFalseyString, $nonFalseyString));
}
