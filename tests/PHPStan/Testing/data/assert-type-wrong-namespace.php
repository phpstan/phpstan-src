<?php // lint >= 8.0

namespace WrongAssertTypeNamespace;

use function SomeWrong\Namespace\assertType;

function doFoo(string $s) {
	assertType('string', $s);
}

