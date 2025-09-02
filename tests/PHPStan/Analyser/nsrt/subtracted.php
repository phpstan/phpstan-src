<?php declare(strict_types = 1);

namespace Substracted;


use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param mixed $date
	 * @param bool $foo
	 */
	public function sayHello($date, $foo): void
	{
		if(is_object($date)){

		} else {
			assertType('mixed~object', $date);

			if ($foo) {
				$date = new \stdClass();
			}
			assertType('mixed~(object~stdClass)', $date);

			if (is_object($date)) {
				assertType('stdClass', $date);
			}
		}
	}
}
