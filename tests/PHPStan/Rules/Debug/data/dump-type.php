<?php

namespace PHPStan;

function (array $a) {
	if ($a === []) {
		return;
	}

	dumpType($a);
	dumpType();
};
