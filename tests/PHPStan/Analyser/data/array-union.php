<?php

namespace ArrayUnion;

use function PHPStan\Testing\assertType;

function doFoo(): bool
{
	return (bool)random_int(0, 1);
}

function () {
	$conditionalArray = [1, 1, 1];
	assertType('array{1, 1, 1}', $conditionalArray);

	if (doFoo()) {
		$conditionalArray[] = 2;
		$conditionalArray[] = 3;

		assertType('array{1, 1, 1, 2, 3}', $conditionalArray);
	}

	assertType('array{1, 1, 1, 2, 3}|array{1, 1, 1}', $conditionalArray);
};
