<?php declare(strict_types = 1);

namespace Bug4371;

class Foo {
}

class Bar extends Foo {

}

class Hello {
	public function foo() {
		if(is_a(Bar::class, Foo::class)) { // should error
			echo "This will never be true";
		} else {
			echo "NO";
		}
	}

	public function bar() {
		if(is_a(Bar::class, Foo::class, false)) { // should error
			echo "This will never be true";
		} else {
			echo "NO";
		}
	}

	public function allFine() {
		if(is_a(Bar::class, Foo::class, true)) { // no error
			echo "This will never be true";
		} else {
			echo "NO";
		}
	}
}
