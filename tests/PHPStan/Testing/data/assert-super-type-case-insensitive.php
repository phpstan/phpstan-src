<?php // lint >= 8.0

namespace MissingTypeCaseSensitive;

function doFoo(string $s) {
	assertSuperTYPe('string', $s);
}

