<?php // lint >= 8.0

namespace MissingTypeCaseSensitive;

function doFoo1(string $s) {
	assertTYPe('string', $s);
}

