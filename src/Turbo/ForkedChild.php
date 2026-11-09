<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

/**
 * What a pcntl_fork()ed child needs from the turbo extension. With the
 * extension active (TurboExtensionEnabler::isActive()), the native class
 * replaces this one.
 *
 * @api
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ForkedChild.cpp')]
final class ForkedChild
{

	/**
	 * Ends the child with _exit() and the engine's exit status, skipping
	 * destructors and the module shutdown of every extension the child
	 * inherited - one waiting for threads the child never had wedges there.
	 * Register it as the child's last shutdown function.
	 *
	 * @throws TurboNotActiveException
	 */
	public static function exitImmediately(): never
	{
		throw new TurboNotActiveException();
	}

}
