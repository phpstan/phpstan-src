<?php // lint >= 8.0
declare(strict_types = 1);
namespace Bug9360;

use function PHPStan\Testing\assertType;

/**
 * @template T of string|bool
 *
 * @param T ...$args
 *
 * @return (T is string ? string : bool)
 */
function environment(string|bool ...$args): string|bool
{
	if (count($args) === 0) {
		return true;
	}
	return (string) $args[0];
}

assertType('string', environment());
assertType('string', environment('APP_ENV'));
assertType('string', environment('APP_ENV', 'production'));
