<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug5207;

use function PHPStan\Testing\assertType;

abstract class HelloWorld {
	abstract public function getChild(): ?HelloWorld;

	public function sayHello(): void {
		$foo = null !== $this->getChild();
		if ($foo) {
			assertType('Bug5207\HelloWorld', $this->getChild());
		}
	}

	public function sayHelloInline(): void {
		if (null !== $this->getChild()) {
			assertType('Bug5207\HelloWorld', $this->getChild());
		}
	}
}

abstract class ImpureWorld {
	/**
	 * @phpstan-impure
	 */
	abstract public function getChild(): ?ImpureWorld;

	public function sayHello(): void {
		$foo = null !== $this->getChild();
		if ($foo) {
			assertType('Bug5207\ImpureWorld|null', $this->getChild());
		}
	}

	public function sayHelloInline(): void {
		if (null !== $this->getChild()) {
			assertType('Bug5207\ImpureWorld|null', $this->getChild());
		}
	}
}
