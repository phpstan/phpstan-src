<?php // lint >= 8.0

namespace WrongAssertTypeNamespace;

use function SomeWrong\Namespace\assertSuperType;

function doFoo(string $s) {
	assertSuperType('string', $s);
}

