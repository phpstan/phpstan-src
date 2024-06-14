<?php

namespace CallableString;

use function PHPStan\Testing\assertType;

/**
 * @param callable-string $callableString
 */
function foo(callable $callable, string $string, string $callableString)
{
	assertType('callable(): mixed', $callable);
	assertType('string', $string);
	assertType('callable-string', $callableString);

	if (is_string($callable)) {
		assertType('callable-string', $callable);
	}

	if (is_callable($string)) {
		assertType('callable-string', $string);
	}

	if ($callable === $string) {
		assertType('callable-string', $callable);
		assertType('callable-string', $string);
	}
}
