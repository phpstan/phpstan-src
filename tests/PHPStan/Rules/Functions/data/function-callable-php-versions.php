<?php // lint >= 8.1

namespace FunctionCallablePhpVersions;

function doFoo(): void
{
}

function doBar(): void
{
	if (PHP_VERSION_ID >= 80100) {
		$f = doFoo(...);
	}

	if (PHP_VERSION_ID < 80100) {
		$g = doFoo(...);
	}

	$h = doFoo(...);
}
