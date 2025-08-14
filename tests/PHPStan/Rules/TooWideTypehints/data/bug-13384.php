<?php declare(strict_types=1);

namespace Bug13384;

use function register_shutdown_function;

final class ShutdownHandlerFalseDefault
{
	private static bool $registered = false;
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

final class ShutdownHandlerTrueDefault
{
	private static bool $registered = true;
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
