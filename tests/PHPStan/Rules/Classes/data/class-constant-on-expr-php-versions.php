<?php // lint >=8.0

namespace ClassConstantOnExprPhpVersions;

function doFoo(object $o): void
{
	if (PHP_VERSION_ID >= 80000) {
		echo $o::class;
	}

	if (PHP_VERSION_ID < 80000) {
		echo $o::class;
	}

	echo $o::class;
}
