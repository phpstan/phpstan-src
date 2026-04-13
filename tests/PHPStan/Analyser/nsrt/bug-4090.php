<?php

declare(strict_types = 1);

namespace Bug4090;

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
		assertType('string', current($a));
		echo trim(current($a));
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
