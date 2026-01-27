<?php declare(strict_types = 1);

namespace Bug13945;

trait Foo {
	public function baz(): void {
		$this->myProperty = "a"; // @phpstan-ignore property.notFound
	}
}

trait Baz {
}

class HelloWorld
{
	use Baz;
	use Foo;
}
