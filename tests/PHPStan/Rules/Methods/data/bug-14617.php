<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug14617;

class MyClass {}

namespace Bug14617\Consumer;

use Bug14617\MyClass as myclass;

function test(): myclass {
	return new myclass();
}

class Foo {
	public function bar(myclass $a): myclass {
		return $a;
	}

	public function nullable(?myclass $a): ?myclass {
		return $a;
	}

	public function union(myclass|string $a): myclass|int {
		return $a;
	}
}
