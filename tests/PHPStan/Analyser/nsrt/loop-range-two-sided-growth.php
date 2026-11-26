<?php declare(strict_types = 1);

namespace LoopRangeTwoSidedGrowth;

use function PHPStan\Testing\assertType;

/** @param list<int<-1, 1>> $steps */
function walkInt(array $steps): int
{
	$position = 0;
	foreach ($steps as $step) {
		$position += $step;
	}
	assertType('int', $position);
	if ($position > 4) {
		echo "far\n";
	}

	return $position;
}

/** @param list<float<-1, 1>> $steps */
function walkFloat(array $steps): float
{
	$position = 0.0;
	foreach ($steps as $step) {
		$position += $step;
	}
	assertType('float<min, max>', $position);
	if ($position > 4.0) {
		echo "far\n";
	}

	return $position;
}

function oscillate(int $limit): int
{
	$value = 0;
	for ($i = 0; $i < $limit; $i++) {
		$value = $i % 2 === 0 ? $value + 3 : $value - 2;
	}
	assertType('int', $value);
	if ($value > 20) {
		echo "high\n";
	}

	return $value;
}

/** @param list<int<0, 1>> $steps */
function oneSided(array $steps): int
{
	$count = 0;
	foreach ($steps as $step) {
		$count += $step;
	}
	assertType('int<0, max>', $count);

	return $count;
}
