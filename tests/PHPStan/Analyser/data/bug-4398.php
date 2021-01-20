<?php

namespace Bug4398;

use function PHPStan\Analyser\assertType;

function (array $meters): void {
	assertType('array', $meters);
	if (empty($meters)) {
		throw new \Exception('NO_METERS_FOUND');
	}

	assertType('array&nonEmpty', $meters);
	assertType('array', array_reverse());
	assertType('array&nonEmpty', array_reverse($meters));
	assertType('array<int, (int|string)>&nonEmpty', array_keys($meters));
	assertType('array<int, mixed>&nonEmpty', array_values($meters));
};
