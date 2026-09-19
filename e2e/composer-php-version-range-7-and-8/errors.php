<?php declare(strict_types = 1);

namespace ComposerPhpVersionRange7And8Errors;

use RuntimeException;

function noncapturingCatch(): void
{
	try {
		throw new RuntimeException('foo');
	} catch (RuntimeException) {
		echo 'failed';
	}
}

function acceptsTwoInts(int $i, int $j): void
{
	echo $i + $j;
}

function namedArguments(): void
{
	acceptsTwoInts(i: 1, j: 2);
}

/**
 * @return bool
 */
function phpdocBoolReturn()
{
	return returnsTrue();
}

/**
 * @return true
 */
function returnsTrue(): bool
{
	return true;
}
