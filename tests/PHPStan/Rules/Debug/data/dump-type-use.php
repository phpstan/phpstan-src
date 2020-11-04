<?php

namespace App\Foo;

use function PHPStan\dumpType;

function (array $a) {
	if ($a === []) {
		return;
	}

	\PHPStan\dumpType($a);
	dumpType($a);
};
