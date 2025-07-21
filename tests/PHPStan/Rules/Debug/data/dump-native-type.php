<?php

namespace PHPStan;

/** @param non-empty-array $b */
function (array $a, array $b) {
	if ($a === []) {
		return;
	}

	dumpNativeType($a);
	dumpNativeType($b);
};
