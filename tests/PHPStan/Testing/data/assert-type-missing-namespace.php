<?php // lint >= 8.0

namespace MissingAssertTypeNamespace;

function doFoo1(string $s) {
	assertType('string', $s);
}

