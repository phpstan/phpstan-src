<?php declare(strict_types = 1);

namespace FloatRangeLoopGeneralize;

use function PHPStan\Testing\assertType;

/** @param list<float<0, 1>> $weights */
function total(array $weights): float
{
	$sum = 0.0;
	foreach ($weights as $weight) {
		$sum += $weight;
	}
	assertType('float<0.0, max>', $sum);
	if ($sum > 4.0) {
		echo "heavy\n";
	}

	return $sum;
}

/** @param list<int<0, 1>> $weights */
function totalInt(array $weights): int
{
	$sum = 0;
	foreach ($weights as $weight) {
		$sum += $weight;
	}
	assertType('int<0, max>', $sum);

	return $sum;
}

/** @param float<0, 1> $step */
function walk(float $step): float
{
	for ($x = 0.0; $x < 10; $x += $step) {
		assertType('float<0.0, 9.999999999999998>', $x);
	}
	assertType('float<10.0, 10.999999999999998>', $x);

	return $x;
}

/** @param int<0, 1> $step */
function walkInt(int $step): int
{
	for ($x = 0; $x < 10; $x += $step) {
		assertType('int<0, 9>', $x);
	}
	assertType('10', $x);

	return $x;
}

function dice(int $rolls): float
{
	$total = 0.0;
	for ($i = 0; $i < $rolls; $i++) {
		$total += random_int(1, 6) / 2;
	}
	assertType('0.0|float<0.5, max>', $total);

	return $total;
}

/** @param list<float<0, 1>> $costs */
function budget(array $costs): float
{
	$budget = 10.0;
	foreach ($costs as $cost) {
		$budget -= $cost;
	}
	assertType('float<min, 10.0>', $budget);

	return $budget;
}

/** @param list<float<1, 2>> $factors */
function compound(array $factors): float
{
	$amount = 100.0;
	foreach ($factors as $factor) {
		$amount *= $factor;
	}
	assertType('float<100.0, max>', $amount);

	return $amount;
}

/**
 * @param list<list<float<0, 1>>> $rows
 * @return array{total: float}
 */
function nested(array $rows): array
{
	$result = ['total' => 0.0];
	foreach ($rows as $row) {
		foreach ($row as $value) {
			$result['total'] += $value;
		}
	}
	assertType('array{total: float<0.0, max>}', $result);

	return $result;
}

/** @param list<float<0, 1>> $weights */
function unchanged(array $weights): float
{
	$max = 0.0;
	foreach ($weights as $weight) {
		if ($weight > $max) {
			$max = $weight;
		}
	}
	assertType('float<0.0, 1.0>', $max);

	return $max;
}
