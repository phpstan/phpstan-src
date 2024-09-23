<?php // lint >= 8.0

namespace SubtractMixed;

use function PHPStan\Testing\assertType;

/**
 * @param int|0.0|''|'0'|array{}|false|null $moreThenFalsy
 */
function subtract(mixed $m, $moreThenFalsy) {
	if ($m !== true) {
		assertType("mixed~true", $m);
		assertType('bool', (bool) $m); // mixed could still contain something truthy
	}
	if ($m !== false) {
		assertType("mixed~false", $m);
		assertType('bool', (bool) $m); // mixed could still contain something falsy
	}
	if (!is_bool($m)) {
		assertType('mixed~bool', $m);
		assertType('bool', (bool) $m);
	}
	if (!is_array($m)) {
		assertType('mixed~array', $m);
		assertType('bool', (bool) $m);
	}

	if ($m) {
		assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $m);
		assertType('true', (bool) $m);
	}
	if (!$m) {
		assertType("0|0.0|''|'0'|array{}|false|null", $m);
		assertType('false', (bool) $m);
	}
	if (!$m) {
		if (!is_int($m)) {
			assertType("0.0|''|'0'|array{}|false|null", $m);
			assertType('false', (bool)$m);
		}
		if (!is_bool($m)) {
			assertType("0|0.0|''|'0'|array{}|null", $m);
			assertType('false', (bool)$m);
		}
	}

	if (!$m || is_int($m)) {
		assertType("0.0|''|'0'|array{}|int|false|null", $m);
		assertType('bool', (bool) $m);
	}

	if ($m !== $moreThenFalsy) {
		assertType('mixed', $m);
		assertType('bool', (bool) $m); // could be true
	}

	if ($m != 0 && !is_array($m) && $m != null && !is_object($m)) { // subtract more types then falsy
		assertType("mixed~(0|0.0|''|'0'|array|object|false|null)", $m);
		assertType('true', (bool) $m);
	}
}
