<?php declare(strict_types = 1);

namespace Bug12457;

class HelloWorld
{
	/**
	 * @param array{numeric-string&uppercase-string&lowercase-string} $a
	 */
	public function sayHello(array $a): void
	{
		/** @var array{numeric-string} $b */
		$b = $a;
	}

	/**
	 * @param callable(): numeric-string&uppercase-string&lowercase-string $a
	 */
	public function sayHello2(callable $a): void
	{
		/** @var callable(): string $b */
		$b = $a;
	}
}
