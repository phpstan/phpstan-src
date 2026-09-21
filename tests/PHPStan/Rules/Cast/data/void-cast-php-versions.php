<?php // lint >= 8.5

namespace VoidCastPhpVersions;

function doFoo(): void
{
	if (PHP_VERSION_ID >= 80500) {
		(void) doBar();
	}

	if (PHP_VERSION_ID < 80500) {
		(void) doBar();
	}

	(void) doBar();
}

function doBar(): int
{
	return 1;
}
