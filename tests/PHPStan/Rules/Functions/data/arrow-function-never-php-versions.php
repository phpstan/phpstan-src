<?php // lint >= 8.2

namespace ArrowFunctionNeverPhpVersions;

function doFoo(): void
{
	if (PHP_VERSION_ID >= 80200) {
		$f = fn (): never => throw new \Exception();
	}

	if (PHP_VERSION_ID < 80200) {
		$g = fn (): never => throw new \Exception();
	}

	$h = fn (): never => throw new \Exception();
}
