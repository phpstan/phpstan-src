<?php // lint >= 8.3

namespace ReadOnlyClassPhpVersions;

if (PHP_VERSION_ID >= 80200) {
	readonly class SupportedInBranch
	{
	}
}

if (PHP_VERSION_ID < 80200) {
	readonly class UnsupportedInBranch
	{
	}
}

readonly class AlwaysUnsupported
{
}

function doFoo(): void
{
	if (PHP_VERSION_ID >= 80300) {
		$supportedInBranch = new readonly class {
		};
	}

	if (PHP_VERSION_ID < 80300) {
		$unsupportedInBranch = new readonly class {
		};
	}

	$alwaysUnsupported = new readonly class {
	};
}
