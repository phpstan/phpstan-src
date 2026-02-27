<?php

namespace Bug5290;

function set(?bool &$value): void {
	$value = true;
}

$array = [];
set($array[]);

var_dump($array);

// Also test with closures and anonymous functions
(function (&$ref) {})($array[]);

class Foo {
	public function bar(?bool &$value): void {
		$value = true;
	}
}

$foo = new Foo();
$foo->bar($array[]);
