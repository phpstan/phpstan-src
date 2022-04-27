<?php // lint >= 8.1

namespace Bug7135;

class HelloWorld
{
	private \Closure $closure;
	public function sayHello(callable $callable): void
	{
		$this->closure = $callable(...);
	}
	public function sayHello2(callable $callable): void
	{
		$this->closure = $this->sayHello(...);
	}
	public function sayHello3(callable $callable): void
	{
		$this->closure = strlen(...);
	}
	public function sayHello4(callable $callable): void
	{
		$this->closure = new HelloWorld(...);
	}
	public function sayHello5(callable $callable): void
	{
		$this->closure = self::doFoo(...);
	}

	public static function doFoo(): void
	{

	}

	public function getClosure(): \Closure
	{
		return $this->closure;
	}
}
