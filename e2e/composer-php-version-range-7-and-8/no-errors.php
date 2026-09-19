<?php declare(strict_types = 1);

namespace ComposerPhpVersionRange7And8;

use RuntimeException;

function unusedCatchVariable(): void
{
	try {
		throw new RuntimeException('foo');
	} catch (RuntimeException $e) {
		// no "Catch variable $e is never read." - the variable cannot be dropped
		// on PHP 7, which has no non-capturing catch
		echo 'failed';
	}
}

// no "can be changed to true" - the native true type does not exist before PHP 8.2
function nativeBoolReturn(): bool
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
