<?php

namespace Bug7272;

use function PHPStan\Testing\assertType;

const FLAG_READ = 'read';
const FLAG_WRITE = 'write';
const OTHER_FLAG = 'other';

define('BUG_7272_LIMIT_LOW', 10);
define('BUG_7272_LIMIT_HIGH', 100);

final class NumericUtil
{

	/**
	 * @param PHP_ROUND_* $mode
	 */
	public static function round(float $num, int $precision = 0, int $mode = PHP_ROUND_HALF_UP): float
	{
		assertType('1|2|3|4', $mode);

		return round($num, $precision, $mode);
	}

}

/**
 * @param \PHP_ROUND_* $fullyQualified
 * @param PHP_ROUND_*|null $nullable
 * @param E_*_ERROR $middle
 * @param FLAG_* $namespaced
 * @param \Bug7272\FLAG_* $fullyQualifiedNamespaced
 * @param Sub\FLAG_* $relative
 * @param BUG_7272_LIMIT_* $defined
 * @param array<PHP_ROUND_*, FLAG_*> $array
 */
function doFoo($fullyQualified, $nullable, $middle, $namespaced, $fullyQualifiedNamespaced, $relative, $defined, $array): void
{
	assertType('1|2|3|4', $fullyQualified);
	assertType('1|2|3|4|null', $nullable);
	assertType('16|64|256|4096', $middle);
	assertType("'read'|'write'", $namespaced);
	assertType("'read'|'write'", $fullyQualifiedNamespaced);
	assertType("'execute'", $relative);
	assertType('10|100', $defined);
	assertType("array<1|2|3|4, 'read'|'write'>", $array);
}

namespace Bug7272\Sub;

use function PHPStan\Testing\assertType;

const PHP_ROUND_SHADOWED = 'shadowed';
const FLAG_EXECUTE = 'execute';

/**
 * @param PHP_ROUND_* $shadowed
 * @param \PHP_ROUND_* $global
 * @param FLAG_* $local
 */
function doBar($shadowed, $global, $local): void
{
	// like PHP does for a constant name, an unqualified pattern looks in the current namespace before the global one
	assertType("'shadowed'", $shadowed);
	assertType('1|2|3|4', $global);
	assertType("'execute'", $local);
}
