<?php // lint >= 8.2

declare(strict_types=1);

namespace Bug13384b;

use function register_shutdown_function;

final class ShutdownHandlerFooBar
{
	private static false $registered = false;
	private static string $message  = '';

	public static function setMessage(string $message): void
	{
		self::register();

		self::$message = $message;
	}

	private static function register(): void
	{
		if (self::$registered) {
			return;
		}

		register_shutdown_function(static function (): void
		{
			print self::$message;
		});
	}
}
