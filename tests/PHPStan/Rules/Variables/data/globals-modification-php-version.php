<?php

namespace GlobalsModificationPhpVersion;

/**
 * @param array<string, mixed> $values
 */
function doFoo(array $values): void
{
	if (PHP_VERSION_ID < 80100) {
		$GLOBALS = $values;
	}

	if (PHP_VERSION_ID >= 80100) {
		$GLOBALS = $values;
	}
}
