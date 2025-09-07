<?php declare(strict_types = 1);

namespace Bug12234;

class Foo {
	public function getSize(): int {
		return 0;
	}
}

function bar(Foo $foo, int $b): true {
	return true;
}

$test = bar(
	$foo = new Foo(),
	$foo->getSize(),
);
