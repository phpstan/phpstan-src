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

abstract class StaticWorld {
	abstract public static function getChild(): ?StaticWorld;

	public static function sayHello(): void {
		$foo = null !== static::getChild();
		if ($foo) {
			assertType('Bug5207\StaticWorld', static::getChild());
		}
	}

	public static function sayHelloInline(): void {
		if (null !== static::getChild()) {
			assertType('Bug5207\StaticWorld', static::getChild());
		}
	}
}

abstract class ImpureStaticWorld {
	/**
	 * @phpstan-impure
	 */
	abstract public static function getChild(): ?ImpureStaticWorld;

	public static function sayHello(): void {
		$foo = null !== static::getChild();
		if ($foo) {
			assertType('Bug5207\ImpureStaticWorld|null', static::getChild());
		}
	}

	public static function sayHelloInline(): void {
		if (null !== static::getChild()) {
			assertType('Bug5207\ImpureStaticWorld|null', static::getChild());
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
