<?php // lint >= 8.0

namespace MissingAssertTypeNamespace;

function doFoo(string $s) {
	assertType('string', $s);
}

