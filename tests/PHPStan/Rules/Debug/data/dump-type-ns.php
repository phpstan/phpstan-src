<?php

namespace App\Foo;

function (array $a) {
	if ($a === []) {
		return;
	}

	\PHPStan\dumpType($a);
	dumpType($a);
};
