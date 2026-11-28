<?php declare(strict_types = 1);

namespace Bug15878;

use function PHPStan\Testing\assertType;

/** @param array<int<0, 3>, string> $a */
function foo(array $a): void
{
	$a[] = 'x';
	assertType('non-empty-array<int<0, 4>, string>', $a);
}

function bar(): void
{
	$a = [];
	for ($i = 0; $i < 4; $i++) {
		$a[$i] = 'x';
	}
	$a[] = 'x';
	assertType('non-empty-array<int<0, 4>, \'x\'>', $a);
	echo $a[4];
}

/** @param array<int<0, 3>|string, string> $a */
function mixedKeys(array $a): void
{
	$a[] = 'x';
	assertType('non-empty-array<int<0, 4>|string, string>', $a);
}

/** @param array<0|int<5, 7>, string> $a */
function constantAndRange(array $a): void
{
	$a[] = 'x';
	assertType('non-empty-array<0|1|int<5, 8>, string>', $a);
}

/** @param array<int<5, max>, string> $a */
function unboundedRange(array $a): void
{
	$a[] = 'x';
	assertType('non-empty-array<int<5, max>, string>', $a);
}
