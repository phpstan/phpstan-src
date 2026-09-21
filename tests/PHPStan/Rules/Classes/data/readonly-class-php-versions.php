<?php // lint >= 8.2

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
		$anonymous = new readonly class {
		};
	}

	$alwaysUnsupported = new readonly class {
	};
}
