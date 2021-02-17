<?php declare(strict_types = 1);

namespace Bug2837;

class HelloWorld
{
	use X;
	/**
	 * @phpstan-param class-string $class
	 */
	public function demo(string $class): void
	{
		$this->make($class);
	}


}

trait X {
	/**
	 * @phpstan-template T of object
	 * @phpstan-param class-string<T> $class
	 * @phpstan-return T
	 */
	protected function make(string $class) : object
	{
		$reflection = new \ReflectionClass($class);

		return $reflection->newInstanceWithoutConstructor();
	}
}

class HelloWorld2
{

	/**
	 * @phpstan-param class-string $class
	 */
	public function demo(string $class): void
	{
		$this->make($class);
	}

	/**
	 * @phpstan-template T of object
	 * @phpstan-param    class-string<T> $class
	 * @phpstan-return   T
	 */
	protected function make(string $class) : object
	{
		$reflection = new \ReflectionClass($class);

		return $reflection->newInstanceWithoutConstructor();
	}
}
