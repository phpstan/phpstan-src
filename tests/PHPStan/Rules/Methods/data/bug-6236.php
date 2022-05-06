<?php

namespace Bug6236;


class HelloWorld
{
	/**
	 * @param \Traversable<mixed, \DateTime> $t
	 */
	public static function sayHello(\Traversable $t): void
	{
	}

	/**
	 * @param \SplObjectStorage<\DateTime, \DateTime> $foo
	 */
	public function doFoo($foo)
	{
		$this->sayHello(new \ArrayIterator([new \DateTime()]));

		$this->sayHello(new \ArrayIterator(['a' => new \DateTime()]));

		$this->sayHello($foo);
	}
}

