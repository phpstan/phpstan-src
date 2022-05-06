<?php

namespace Bug6591;

interface BaseInterface {}

interface ExtendedInterface extends BaseInterface {}

/**
 * @template T of BaseInterface
 */
class HelloWorld
{
	/**
	 * @param T $class
	 */
	public function hello(BaseInterface $class): void
	{
		if ($class instanceof ExtendedInterface) {
			echo 'Extended', PHP_EOL;
		}
		$this->sayHello($class);
	}

	/**
	 * @param T $class
	 */
	public function sayHello(BaseInterface $class): void
	{
		echo 'Hello', PHP_EOL;
	}
}
