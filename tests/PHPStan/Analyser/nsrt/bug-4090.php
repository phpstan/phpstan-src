<?php declare(strict_types = 1);

namespace Bug4090;

use function current;
use function PHPStan\Testing\assertType;

/** @param string[] $a */
function foo(array $a): void
{
	if (count($a) > 1) {
		echo implode(',', $a);
	} elseif (count($a) === 1) {
		assertType('string', current($a));
		echo trim(current($a));
	}
}


/** @param string[] $a */
function bar(array $a): void
{
	$count = count($a);
	if ($count > 1) {
		echo implode(',', $a);
	} elseif ($count === 1) {
		// Count narrowing via intermediate variable doesn't propagate
		// to the array — the guard type int<0, max> is non-finite.
		assertType('string|false', current($a));
		echo trim((string) current($a));
	}
}


/** @param string[] $a */
function qux(array $a): void
{
	switch (count($a)) {
		case 0:
			break;
		case 1:
			assertType('string', current($a));
			echo trim(current($a));
			break;
		default:
			echo implode(',', $a);
			break;
	}
}
