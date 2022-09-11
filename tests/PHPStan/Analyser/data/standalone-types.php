<?php // lint >= 8.2

namespace StandaloneTypes;

use function PHPStan\Testing\assertType;

function foo(): null {
	return null;
}
function bar(): false {
	return false;
}

class standalone {
	function foo(): null {
		return null;
	}
	function bar(): false {
		return false;
	}
}

function doFoo() {
	assertType('null', foo());
	assertType('false', bar());

	$s = new standalone();

	assertType('null', $s->foo());
	assertType('false', $s->bar());
}
