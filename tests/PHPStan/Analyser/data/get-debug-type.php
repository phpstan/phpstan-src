<?php // onlyif PHP_VERSION_ID >= 80000

namespace GetDebugType;

use function PHPStan\Testing\assertType;

final class A {}

/**
 * @param double $d
 * @param resource $r
 * @param int|string $intOrString
 * @param array|A $arrayOrObject
 */
function doFoo(bool $b, int $i, float $f, $d, $r, string $s, array $a, $intOrString, $arrayOrObject) {
	$null = null;
	$resource = fopen('php://memory', 'r');
	$o = new \stdClass();
	$A = new A();
	$anonymous = new class {};

	assertType("'bool'", get_debug_type($b));
	assertType("'bool'", get_debug_type(true));
	assertType("'bool'", get_debug_type(false));
	assertType("'int'", get_debug_type($i));
	assertType("'float'", get_debug_type($f));
	assertType("'float'", get_debug_type($d));
	assertType("'string'", get_debug_type($s));
	assertType("'array'", get_debug_type($a));
	assertType("string", get_debug_type($o));
	assertType("'GetDebugType\\\\A'", get_debug_type($A));
	assertType("string", get_debug_type($r));
	assertType("'bool'|string", get_debug_type($resource));
	assertType("'null'", get_debug_type($null));
	assertType("'int'|'string'", get_debug_type($intOrString));
	assertType("'array'|'GetDebugType\\\\A'", get_debug_type($arrayOrObject));
	assertType("'class@anonymous'", get_debug_type($anonymous));
}

/**
 * @param non-empty-string $nonEmptyString
 * @param non-falsy-string $falsyString
 * @param numeric-string $numericString
 * @param class-string $classString
 */
function strings($nonEmptyString, $falsyString, $numericString, $classString) {
	assertType("'string'", get_debug_type($nonEmptyString));
	assertType("'string'", get_debug_type($falsyString));
	assertType("'string'", get_debug_type($numericString));
	assertType("'string'", get_debug_type($classString));
}
