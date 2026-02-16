<?php

namespace PHPStan;

function (array $a, array $b) {
	if ($a === []) {
		return;
	}

	dumpType($a, $b);
};
