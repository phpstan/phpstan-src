<?php // lint >= 8.0

namespace WrongAssertTypeNamespace;

use function SomeWrong\Namespace\assertType;

function doFoo1(string $s) {
	assertType('string', $s);
}

