<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions\data;

class ReturnThrowableNoImplicitThrows
{
	public function sayHello(): void
	{
		try {
			$this->returnVoid();
		} catch (\Throwable $e) { // dead cache properly not reported (implicitThrows is enabled)

		}

		try {
			$this->returnThrowable();
		} catch (\Throwable $e) { // dead cache REPORTED

		}
	}

	public function returnVoid(): void {

	}

	public function returnThrowable(): \Exception {
		return new \Exception();
	}
}
