<?php declare(strict_types = 1);

namespace Bug9826;

class Foo
{

	public function returnVoid(): void
	{

	}

	public function returnThrowable(): \Exception
	{
		return new \Exception();
	}

	public static function staticReturnThrowable(): \Exception
	{
		return new \Exception();
	}

	public function sayHello(): void
	{
		try {
			$this->returnVoid();
		} catch (\Throwable $e) {
			// ok - implicit throws enabled
		}

		try {
			$this->returnThrowable();
		} catch (\Throwable $e) {
			// ok - method can implicitly throw even though it returns Throwable
		}

		try {
			self::staticReturnThrowable();
		} catch (\Throwable $e) {
			// ok - static method can implicitly throw even though it returns Throwable
		}

		try {
			returnThrowable();
		} catch (\Throwable $e) {
			// ok - function can implicitly throw even though it returns Throwable
		}

		try {
			new \Exception('test');
		} catch (\Throwable $e) {
			// ok - new Exception() can implicitly throw even though it constructs Throwable
		}
	}

}

function returnThrowable(): \Exception
{
	return new \Exception();
}

function triggerErrorNeverReturns(): void
{
	try {
		$a = trigger_error("hello", E_USER_ERROR);
	} catch (\Exception $e) {
		// ok - trigger_error returns never (explicit), gets throw point like any never-returning function
	}
}
