<?php

declare(strict_types = 1);

namespace Bug14211;

use function PHPStan\Testing\assertType;

/** @param array<mixed> $data */
function doSomething(array $data): bool
{
	if (!isset($data['x'])) {
		return false;
	}

	$m = isset($data['y']);

	if ($m) {
		assertType('true', $m);
	}
	assertType('bool', $m);

	if ($m) {
		assertType('true', $m);
	}

	return true;
}
