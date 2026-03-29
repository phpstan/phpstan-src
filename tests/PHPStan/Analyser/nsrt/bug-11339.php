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
