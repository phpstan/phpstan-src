<?php // lint >= 8.0

namespace MissingAssertNativeNamespace;

function doFoo(string $s) {
	assertNativeType('string', $s);
}
