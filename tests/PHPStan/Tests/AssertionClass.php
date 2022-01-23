<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use function is_int;

class AssertionClass
{

	/** @throws AssertionException */
	public function assertString(?string $arg): bool
	{
		if ($arg === null) {
			throw new AssertionException();
		}
		return true;
	}

	/** @throws AssertionException */
	public static function assertInt(?int $arg): bool
	{
		if ($arg === null) {
			throw new AssertionException();
		}
		return true;
	}

	/**
	 * @param mixed $arg
	 * @throws AssertionException
	 */
	public function assertNotInt($arg): bool
	{
		if (is_int($arg)) {
			throw new AssertionException();
		}

		return true;
	}

}
