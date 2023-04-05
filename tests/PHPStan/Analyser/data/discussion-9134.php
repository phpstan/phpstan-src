<?php declare(strict_types = 1);

namespace Discussion9134;

use function PHPStan\Testing\assertType;

$var = $_GET["data"];
$res = filter_var($var, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
assertType('array<int>|false', $res);
if (is_array($res) === false) {
	throw new \RuntimeException();
}
