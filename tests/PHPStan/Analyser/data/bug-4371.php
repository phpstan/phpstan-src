<?php

declare(strict_types=1);

namespace Bug4371;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param Exception $exception
	 * @param class-string $classString
	 */
	public function sayHello(Exception $exception, $classString): void
	{
		assertType('bool', is_a($exception, $classString));
		assertType('bool', is_a($exception, $classString, true));
		assertType('bool', is_a($exception, $classString, false));

		$exceptionClass = get_class($exception);
		assertType('false', is_a($exceptionClass, $classString));
		assertType('bool', is_a($exceptionClass, $classString, true));
		assertType('false', is_a($exceptionClass, $classString, false));
	}
}
