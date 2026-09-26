<?php // lint >= 8.5

namespace DeprecatedCastPhpVersions;

function doFoo($value): void
{
	if (PHP_VERSION_ID >= 80500) {
		(integer) $value;
	}

	if (PHP_VERSION_ID < 80500) {
		(integer) $value;
	}

	(integer) $value;
}
