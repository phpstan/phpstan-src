<?php declare(strict_types = 1);

namespace PHPStan\Tests;

class AssertionClass
{

	/** @throws \PHPStan\Tests\AssertionException */
	public function assertString(?string $arg): bool
	{
		if ($arg === null) {
			throw new \PHPStan\Tests\AssertionException();
		}
		return true;
	}

	/** @throws AssertionException */
	public static function assertInt(?int $arg): bool
	{
		if ($arg === null) {
			throw new \PHPStan\Tests\AssertionException();
		}
		return true;
	}

	/**
	 * @param mixed $arg
	 * @return bool
	 * @throws AssertionException
	 */
	public function assertNotInt($arg): bool
	{
		if (is_int($arg)) {
			throw new \PHPStan\Tests\AssertionException();
		}

		return true;
	}

}
