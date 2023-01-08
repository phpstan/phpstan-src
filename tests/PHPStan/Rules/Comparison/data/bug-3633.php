<?php declare(strict_types = 1);

namespace Bug3633;

trait Foo {
	public function test($obj): void {
		if (get_class($this) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($this) === OtherClass::class) {
			echo "OK";
		}

		if (get_class() === HelloWorld::class) {
			echo "OK";
		}
		if (get_class() === OtherClass::class) {
			echo "OK";
		}

		if (get_class($obj) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($obj) === OtherClass::class) {
			echo "OK";
		}
	}
}

class HelloWorld {
	use Foo;

	public function bar($obj): void {
		if (get_class($this) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($this) === OtherClass::class) {
			echo "OK";
		}

		if (get_class() === HelloWorld::class) {
			echo "OK";
		}
		if (get_class() === OtherClass::class) {
			echo "OK";
		}

		if (get_class($obj) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($obj) === OtherClass::class) {
			echo "OK";
		}


		$this->test();
	}
}

class OtherClass {
	use Foo;

	public function bar($obj): void {
		if (get_class($this) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($this) === OtherClass::class) {
			echo "OK";
		}

		if (get_class() === HelloWorld::class) {
			echo "OK";
		}
		if (get_class() === OtherClass::class) {
			echo "OK";
		}

		if (get_class($obj) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($obj) === OtherClass::class) {
			echo "OK";
		}

		$this->test();
	}
}

final class FinalClass {
	use Foo;

	public function bar($obj): void {
		if (get_class($this) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($this) === OtherClass::class) {
			echo "OK";
		}
		if (get_class($this) !== FinalClass::class) {
			echo "OK";
		}
		if (get_class($this) === FinalClass::class) {
			echo "OK";
		}

		if (get_class() === HelloWorld::class) {
			echo "OK";
		}
		if (get_class() === OtherClass::class) {
			echo "OK";
		}
		if (get_class() !== FinalClass::class) {
			echo "OK";
		}
		if (get_class() === FinalClass::class) {
			echo "OK";
		}

		if (get_class($obj) === HelloWorld::class) {
			echo "OK";
		}
		if (get_class($obj) === OtherClass::class) {
			echo "OK";
		}

		$this->test();
	}
}
