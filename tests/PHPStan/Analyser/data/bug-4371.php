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
	public function sayHello(Exception $exception, $classString, string $s): void
	{
		assertType('bool', is_a($exception, $classString));
		assertType('bool', is_a($exception, $classString, true));
		assertType('bool', is_a($exception, $classString, false));

		$exceptionClass = get_class($exception);
		assertType('false', is_a($exceptionClass, $classString));
		assertType('bool', is_a($exceptionClass, $classString, true));
		assertType('false', is_a($exceptionClass, $classString, false));

		assertType('false', is_a($exceptionClass, $s));
		assertType('bool', is_a($exceptionClass, $s, true));
		assertType('false', is_a($exceptionClass, $s, false));
	}
}
