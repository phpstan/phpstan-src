<?php

namespace Bug3465;

trait BarTrait {
	protected function setValue() {}
}


class Foo
{
	use BarTrait {
		setValue as public;
	}
}

function (): void {
	$a = new Foo();
	$a->setValue();
};
