<?php

namespace ExplodeConstantPhp7;

use function PHPStan\Testing\assertType;

/**
 * @param ''|',' $maybeEmptyDelimiter
 */
function constantSplit(string $maybeEmptyDelimiter): void
{
	assertType("array{'a', 'b', 'c'}", explode(',', 'a,b,c'));

	// the empty separator makes explode() return false before PHP 8
	assertType("array{'a', 'b'}|false", explode($maybeEmptyDelimiter, 'a,b'));
}

/**
 * @param ''|',' $maybeEmptyDelimiter
 */
function narrowedPhpVersion(string $maybeEmptyDelimiter): void
{
	if (PHP_VERSION_ID >= 80000) {
		// the scope wins over the configured PHP 7.4
		assertType("array{'a', 'b'}", explode($maybeEmptyDelimiter, 'a,b'));
		assertType('*NEVER*', explode('', 'a,b'));
	}
}
