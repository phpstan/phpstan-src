<?php declare(strict_types=1);

namespace Bug13384Phpdoc;

use function register_shutdown_function;

final class ShutdownHandlerPhpdocTypes
{
	/**
	 * @var bool
	 */
	private static $registered = false;
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
