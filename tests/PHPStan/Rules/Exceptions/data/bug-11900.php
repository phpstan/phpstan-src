<?php // lint >= 8.4

namespace Bug11900;

use Exception;
use Throwable;

abstract class ADataException extends Exception
{
	/**
	 * @return void
	 * @throws static
	 */
	public function throw1(): void
	{
		throw $this;
	}

	/**
	 * @return void
	 * @throws static
	 */
	public static function throw2(): void
	{
		throw new static();
	}
}

final class TestDataException extends ADataException
{
}

class TestPhpStan
{
	/**
	 * @throws TestDataException
	 */
	public function validate(TestDataException $e): void
	{
		$e->throw1();
	}

	/**
	 * @throws TestDataException
	 */
	public function validate2(): void
	{
		TestDataException::throw2();
	}
}
