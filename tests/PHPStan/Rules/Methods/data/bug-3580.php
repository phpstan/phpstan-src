<?php declare(strict_types = 1);

namespace Bug3580;

interface TheBaseFoo {
	/**
	 * @return static
	 */
	public function getClone(): self;
}

interface TheFoo extends TheBaseFoo {
}

abstract class BaseFoo implements TheBaseFoo {
	public function getClone(): self {
		return clone $this;
	}
}

final class Foo extends BaseFoo implements TheFoo {
}

final class TheFooMaker {
	public Foo $foo;

	public function make(): TheFoo {
		return $this->foo->getClone();
	}
}


