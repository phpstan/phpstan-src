<?php // lint >= 8.6

namespace PartialFunctionApplicationPhpVersions;

function doFoo(int $a): int
{
	return $a;
}

function doBar(): void
{
	if (PHP_VERSION_ID >= 80600) {
		$f = doFoo(?);
	}

	if (PHP_VERSION_ID < 80600) {
		$g = doFoo(?);
	}

	$h = doFoo(?);
}
