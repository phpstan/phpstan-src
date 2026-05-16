<?php declare(strict_types = 1);

namespace Bug14617Classes;

class MyClass {}

interface MyInterface {}

namespace Bug14617Classes\Consumer;

use Bug14617Classes\MyClass as myclass;
use Bug14617Classes\MyInterface as myinterface;

class Foo extends myclass implements myinterface {
	public myclass $prop;
}

function test(mixed $x): void {
	if ($x instanceof myclass) {
	}
}
