<?php // lint >= 8.0

namespace MissingAssertTypeNamespace;

function doFoo(string $s) {
	assertSuperType('string', $s);
}

