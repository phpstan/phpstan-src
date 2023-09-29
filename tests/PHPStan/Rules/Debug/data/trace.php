<?php

namespace Trace;

function (array $a) {
	if ($a === []) {
		return;
	}

	/** @phpstan-trace $a */;

	/** @phpstan-trace $a */
	$a = 10;

	/** @phpstan-trace $b */;
};
