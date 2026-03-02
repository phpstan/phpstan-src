<?php declare(strict_types = 1);

namespace Bug13566;

class ReturnViaBool
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

class ReturnViaInt
{
	/** @return ($exit is 1 ? never : void) */
	public static function notFound(int $exit = 1): void
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		if ($exit == 1) {
			echo '404 Not Found';
			exit;
		}
	}

	public function test(): void
	{
		// send 404 header
		self::notFound(0);
	}
}

class ReturnViaString
{
	/** @return ($exit is '1' ? never : void) */
	public static function notFound(string $exit = '1'): void
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		if ($exit == '1') {
			echo '404 Not Found';
			exit;
		}
	}

	public function test(): void
	{
		// send 404 header
		self::notFound('0');
	}
}

class ReturnViaIntNonNative
{
	/** @return ($exit is 1 ? never : void) */
	public static function notFound(int $exit = 1)
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		if ($exit == 1) {
			echo '404 Not Found';
			exit;
		}
	}

	public function test(): void
	{
		// send 404 header
		self::notFound(0);
	}
}

class ReturnsMaybeNever
{
	/** @return ($exit is true ? never : 1) */
	public static function notFound(bool $exit = true)
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		if ($exit) {
			echo '404 Not Found';
			exit;
		}
		return 1;
	}

	public function test(): void
	{
		// send 404 header
		self::notFound(false);

	}
}

class ReturnsMaybeVoid
{
	/** @return ($exit is true ? void : 1) */
	public static function notFound(bool $exit = true)
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		if ($exit) {
			echo '404 Not Found';
			return;
		}
		return 1;
	}

	public function test(): void
	{
		// send 404 header
		self::notFound(false);

	}
}



class ReturnsWithInstanceMethod
{
	/** @return ($exit is true ? never-return : void) */
	public function notFound(bool $exit = true): void
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		if ($exit) {
			echo '404 Not Found';
			exit;
		}
	}


	public function test(): void
	{
		// send 404 header
		$this->notFound(false);

	}
}

/** @return ($exit is true ? never-return : void) */
function notFound(bool $exit = true): void
{
	header('HTTP/1.1 404 Not Found', true, 404);

	if ($exit) {
		echo '404 Not Found';
		exit;
	}
}

function test(): void
{
	// send 404 header
	notFound(false);
}


class ReturnViaUnion
{
	/**
	 * @return ($exit is int ? void : never)
	 */
	public static function notFound(int|string $exit = 'hello world'): void
	{
		header('HTTP/1.1 404 Not Found', true, 404);

		if (!is_int($exit)) {
			echo '404 Not Found';
			exit;
		}
	}

	public function test(): void
	{
		// send 404 header
		self::notFound(0);
	}
}
