<?php // lint >= 8.4

namespace Bug11900;

use Exception;
use Throwable;

abstract class ADataException extends Exception
{
	public int $i {
		/** @throws static */
		get {
			if (rand(0, 1)) {
				throw new static();
			}

			return 42;
		}
	}

	/** @throws static */
	public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
	{
		if (rand(0, 1)) {
			throw new static();
		}

		parent::__construct($message, $code, $previous);
	}

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
