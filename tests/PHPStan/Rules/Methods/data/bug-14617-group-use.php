<?php declare(strict_types = 1);

namespace Bug14617GroupUseMethod;

class MyClass {}
class AnotherClass {}

namespace Bug14617GroupUseMethod\Consumer;

use Bug14617GroupUseMethod\{MyClass as myclass, AnotherClass as anotherclass};

class Foo {
	public function bar(myclass $a): anotherclass {
		return new anotherclass();
	}
}
