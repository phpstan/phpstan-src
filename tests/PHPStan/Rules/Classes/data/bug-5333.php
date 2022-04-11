<?php

namespace Bug5333;

class Route {}

class HelloWorld
{
	/**
	 * @param Route|callable():Route $foo
	 *
	 * @return Route
	 */
	public function sayHello($foo): Route
	{
		if (\is_callable($foo)) {
			$res = $foo();
			if (!$res instanceof Route) {
				throw new \Exception();
			}

			return $res;
		}

		return $foo;
	}
}

class HelloWorld2
{
	/**
	 * @var Route|callable():Route
	 */
	private $foo;

	/**
	 * @param Route|callable():Route $foo
	 */
	public function setFoo($foo): void
	{
		$this->foo = $foo;
	}

	public function getFoo(): Route
	{
		if (\is_callable($this->foo)) {
			$res = ($this->foo)();
			if (!$res instanceof Route) {
				throw new \Exception();
			}

			return $res;
		}

		return $this->foo;
	}
}
