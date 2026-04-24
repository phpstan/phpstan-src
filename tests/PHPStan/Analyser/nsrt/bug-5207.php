<?php

namespace Bug5207;

use function PHPStan\Testing\assertType;

abstract class HelloWorld
{

	abstract public function getChild(): ?HelloWorld;

	public function sayHello(): HelloWorld
	{
		$foo = null !== $this->getChild();

		if ($foo) {
			assertType(HelloWorld::class, $this->getChild());
			return $this->getChild();
		}

		throw new \Exception();
	}

}
