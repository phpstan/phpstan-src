<?php

namespace Bug4371;

class Foo {
}

class Bar extends Foo {

}

class HalloWorld {
	public function doFoo() {
		if(is_a(Bar::class, Foo::class)) { // should be reported
			echo "This will never be true";
		} else {
			echo "NO";
		}
	}
	public function doBar() {
		if(is_a(Bar::class, Foo::class, false)) { // should be reported
			echo "This will never be true";
		} else {
			echo "NO";
		}
	}
}

