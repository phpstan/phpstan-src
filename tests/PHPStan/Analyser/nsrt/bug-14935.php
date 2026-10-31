<?php declare(strict_types = 1);

namespace Bug14935;

use function PHPStan\Testing\assertType;

/**
 * @param list<list<float>> $groups
 */
function countRejected(array $groups): int
{
	$rejected = 0;

	foreach ($groups as $readings) {
		$copy = [];
		foreach ($readings as $reading) {
			$copy[] = $reading;
		}
		assertType('list<float>', $copy);

		if (count($copy) > 1) {
			$rejected++;
		}
	}

	assertType('int<0, max>', $rejected);

	return $rejected;
}
