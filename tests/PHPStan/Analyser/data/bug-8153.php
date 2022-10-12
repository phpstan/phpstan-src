<?php declare(strict_types = 1);

namespace Bug8153;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(callable $foo): void
	{
		if (\is_string($foo) && strlen($foo) > 0) {
			assertType('callable(): mixed&non-empty-string', $foo);
			$this->nonEmptyStringParameter($foo);
		}

		if (\is_string($foo) && '' !== $foo) {
			assertType('callable(): mixed&non-empty-string', $foo);
			$this->nonEmptyStringParameter($foo);
		}
	}

	/**
	 * @param non-empty-string $foo
	 */
	private function nonEmptyStringParameter(string $foo): void
	{
	}
}
