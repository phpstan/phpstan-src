<?php // lint >= 8.0

namespace MissingAssertNativeCaseSensitive;

function doFoo(string $s) {
	assertNATIVEType('string', $s);
}
