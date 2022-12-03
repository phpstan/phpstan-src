<?php declare(strict_types = 1);

namespace Bug3633;

trait Foo {
	public function test(): void {
		if (get_class($this) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($this) === OtherClass::class) {
			echo "OK";
		}
	}
}

class HelloWorld {
	use Foo;

	public function bar(): void {
		$this->test();
	}
}

class OtherClass {
	use Foo;

	public function bar(): void {
		$this->test();
	}
}
