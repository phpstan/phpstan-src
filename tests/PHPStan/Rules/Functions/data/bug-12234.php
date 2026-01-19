<?php

namespace Bug12234;

class Foo {
	public function getSize(): int {
		return 0;
	}
}

function bar(Foo $foo, int $b): bool {
	return true;
}

$test = bar(
	$foo = new Foo(),
	$foo->getSize(),
);
