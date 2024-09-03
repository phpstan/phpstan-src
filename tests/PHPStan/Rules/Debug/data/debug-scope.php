<?php

namespace DebugScope;

use function PHPStan\debugScope;

debugScope();

function (int $a, int $b, bool $debug): void {
	debugScope();

	if ($debug) {
		$c = 1;
	}

	debugScope();
};
