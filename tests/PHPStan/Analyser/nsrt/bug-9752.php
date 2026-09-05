<?php

namespace Bug9752;

use function PHPStan\Testing\assertType;

function isOkOrNot(string $type, ?object $car): void {
	$supportedTypes = ['compact', '4x4'];
	$hasType = \in_array($type, $supportedTypes);

	if (null === $car && true === $hasType) {
		echo 'Not OK';
	} elseif ('compact' === $type) {
		assertType('true', $hasType);
		assertType('object', $car);
		displayOk($car);
	}
}

function displayOk(object $car): void
{
	echo 'OK';
}
