<?php

namespace MinMaxArrays;

use function PHPStan\Analyser\assertType;

function dummy(): void
{
	assertType('1', min([1]));
	assertType('false', min([]));
	assertType('1', max([1]));
	assertType('false', max([]));
}

/**
 * @param int[] $ints
 */
function dummy2(array $ints): void
{
	if (count($ints) !== 0) {
		assertType('int', min($ints));
		assertType('int', max($ints));
	} else {
		assertType('false', min($ints));
		assertType('false', max($ints));
	}
}

/**
 * @param int[] $ints
 */
function dummy3(array $ints): void
{
	assertType('(int|false)', min($ints));
	assertType('(int|false)', max($ints));
}
