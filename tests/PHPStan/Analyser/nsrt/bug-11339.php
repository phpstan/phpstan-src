<?php declare(strict_types = 1);

namespace Bug11339;

use function PHPStan\Testing\assertType;

// assume query string to be ?a[a][b][]=a&a[a][b][]=b

$f = filter_input(INPUT_GET, 'a', FILTER_DEFAULT, FILTER_FORCE_ARRAY);
assertType('array<array|string|false>|null', $f);

$g = filter_input(INPUT_GET, 'a', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
assertType('array<array|string|false>|false|null', $g);

$h = filter_input(INPUT_GET, 'a', FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY);
assertType('array<array|int|false>|null', $h);

$i = filter_input(INPUT_GET, 'a', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
assertType('array<array|int|false>|false|null', $i);

// filter_var with known scalar should not include array in value type
$j = filter_var('foo', FILTER_DEFAULT, FILTER_FORCE_ARRAY);
assertType("array<'foo'>", $j);

$k = filter_var('foo', FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY);
assertType('array<false>', $k);

/**
 * @param array<int> $arrayOfInt
 * @param array<array<int>> $arrayOfArrayOfInt
 * @param array<int|array<int>> $arrayOfIntOrArrayOfInt
 */
function typedArrayInputs(array $arrayOfInt, array $arrayOfArrayOfInt, array $arrayOfIntOrArrayOfInt): void
{
	// array<int> - values are ints, not arrays, so no array in value type
	$l = filter_var($arrayOfInt, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]);
	assertType('array<int>', $l);

	$m = filter_var($arrayOfInt, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE]);
	assertType('array<int>', $m);

	// array<array<int>> - values are arrays, recursively filtered
	$n = filter_var($arrayOfArrayOfInt, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]);
	assertType('array<array<int>>', $n);

	$o = filter_var($arrayOfArrayOfInt, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE]);
	assertType('array<array<int>>', $o);

	// array<int|array<int>> - mixed values, both scalar and array possible
	$p = filter_var($arrayOfIntOrArrayOfInt, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY]);
	assertType('array<array<int>|int>', $p);

	$q = filter_var($arrayOfIntOrArrayOfInt, FILTER_VALIDATE_INT, ['flags' => FILTER_REQUIRE_ARRAY|FILTER_NULL_ON_FAILURE]);
	assertType('array<array<int>|int>', $q);

	// FORCE_ARRAY with typed arrays
	$r = filter_var($arrayOfInt, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]);
	assertType('array<int>', $r);

	$s = filter_var($arrayOfArrayOfInt, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]);
	assertType('array<array<int>>', $s);
}
