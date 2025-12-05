<?php

namespace Strtr;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $nonEmptyString
 * @param non-falsy-string $nonFalseyString
 * @param mixed $mixed
 */
function doFoo(string $s, $nonEmptyString, $nonFalseyString, $mixed) {
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

	assertType('string', strtr($s, [$s => $nonEmptyString]));
	assertType('string', strtr($s, [$nonEmptyString => $nonEmptyString]));
	assertType('string', strtr($s, [$nonFalseyString => $nonFalseyString]));

	assertType('non-empty-string', strtr($nonEmptyString, [$s => $nonEmptyString]));
	assertType('non-empty-string', strtr($nonEmptyString, [$nonEmptyString => $nonEmptyString]));
	assertType('non-empty-string', strtr($nonEmptyString, [$nonFalseyString => $nonFalseyString]));

	assertType('non-empty-string', strtr($nonFalseyString, [$s => $nonEmptyString]));
	assertType('non-falsy-string', strtr($nonFalseyString, [$nonEmptyString => $nonFalseyString]));
	assertType('non-falsy-string', strtr($nonFalseyString, [$nonFalseyString => $nonFalseyString]));

	assertType('non-empty-string', strtr($nonEmptyString, rand(0, 1) ? [$s => $nonEmptyString] : null));
	assertType('non-empty-string', strtr($nonEmptyString, rand(0, 1) ? [$nonEmptyString => $nonEmptyString] : null));
	assertType('non-empty-string', strtr($nonEmptyString, rand(0, 1) ? [$nonFalseyString => $nonFalseyString] : null));

	assertType('non-empty-string', strtr($nonFalseyString, rand(0, 1) ? [$s => $nonEmptyString] : null));
	assertType('non-falsy-string', strtr($nonFalseyString, rand(0, 1) ? [$nonEmptyString => $nonFalseyString] : null));
	assertType('non-falsy-string', strtr($nonFalseyString, rand(0, 1) ? [$nonFalseyString => $nonFalseyString] : null));

	assertType('string', strtr($nonEmptyString, $mixed));
	assertType('string', strtr($nonFalseyString, $mixed));
}
