<?php // lint >= 8.1

namespace ArrayUnpackingPhpVersions;

/**
 * @param array<string, string> $a
 */
function doFoo(array $a): void
{
	if (PHP_VERSION_ID < 80100) {
		$unsupportedInBranch = [...$a];
	}

	if (PHP_VERSION_ID >= 80100) {
		$supportedInBranch = [...$a];
	}

	$always = [...$a];
}
