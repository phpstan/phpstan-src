<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use Closure;
use function restore_error_handler;
use function set_error_handler;

/**
 * Runs something that predictably emits diagnostics which are none of the user's business, and keeps
 * them to itself.
 *
 * Not the `@` operator, and not Composer's `Silencer` either: both work by lowering what is reported,
 * and PHP calls a user error handler for a diagnostic regardless of `error_reporting()`. A handler that
 * does not consult it therefore still sees everything `@` was meant to hide, and Xdebug's `scream`
 * disables `@` outright. A handler of our own, on top of the stack for the duration of the call, is
 * called instead of theirs and reports nothing - and theirs is back in place afterwards.
 *
 * Use it only where the diagnostics are expected and meaningless, never to hide a failure that should
 * be handled: the callable's own return value still says whether it worked.
 */
final class Silencer
{

	/**
	 * @template T
	 * @param Closure(): T $callback
	 * @return T
	 */
	public static function call(Closure $callback)
	{
		set_error_handler(static fn (): bool => true);

		try {
			return $callback();
		} finally {
			restore_error_handler();
		}
	}

}
