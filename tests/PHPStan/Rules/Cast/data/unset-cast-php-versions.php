<?php // lint < 8.0

namespace UnsetCastPhpVersions;

function ($a): void {
	if (PHP_VERSION_ID < 80000) {
		$supportedInBranch = (unset) $a;
	}

	if (PHP_VERSION_ID >= 80000) {
		$unsupportedInBranch = (unset) $a;
	}

	$alwaysUnsupported = (unset) $a;
};
