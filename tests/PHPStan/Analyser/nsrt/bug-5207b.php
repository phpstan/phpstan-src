<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug5207b;

use function PHPStan\Testing\assertType;

abstract class HelloWorld {
	abstract public function getChild(): ?HelloWorld;

	public function sayHello(): void {
		$foo = null !== $this->getChild()->getChild();
		if ($foo) {
			assertType('Bug5207\HelloWorld', $this->getChild());
			assertType('Bug5207\HelloWorld', $this->getChild()->getChild());
			assertType('Bug5207b\HelloWorld|null', $this->getChild()->getChild()->getChild());
		}
	}
}
