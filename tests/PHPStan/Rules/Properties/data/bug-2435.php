<?php declare(strict_types = 1);

namespace Bug2435;

class Foo {
	/** @var Bar|null */
	private $root;

	public function checkRoot(): bool {
		return $this->root?->root !== null;
	}
}

class Bar extends Foo {
}
