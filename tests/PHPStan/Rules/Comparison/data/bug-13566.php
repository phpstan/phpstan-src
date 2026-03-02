<?php declare(strict_types = 1);

namespace Bug13566;

class HelloWorld
{
	/** @return ($exit is true ? never : void) */
	public static function notFound(bool $exit = true): void
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		if ($exit) {
			echo '404 Not Found';
			exit;
		}
	}

	public function test(): void
	{
		// send 404 header without exiting
		self::notFound(false);
	}
}
