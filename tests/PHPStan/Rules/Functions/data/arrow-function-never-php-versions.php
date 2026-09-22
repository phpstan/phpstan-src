<?php // lint >= 8.1

namespace ArrowFunctionNeverPhpVersions;

function (): void {
	if (PHP_VERSION_ID < 80200) {
		$unsupportedInBranch = fn (): never => throw new \Exception();
	}

	if (PHP_VERSION_ID >= 80200) {
		$supportedInBranch = fn (): never => throw new \Exception();
	}

	$always = fn (): never => throw new \Exception();
};
