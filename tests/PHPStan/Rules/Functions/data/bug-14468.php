<?php

namespace Bug14468;

function test(string $s): void
{
	$min = 0;
	$max = strlen($s);
	while ($min < $max) {
		$rand = random_int($min, $max);
		$min++;
	}
}

/**
 * @param int<0, max> $a
 * @param int<0, max> $b
 */
function bothUnboundedMax(int $a, int $b): void
{
	random_int($a, $b);
}

/**
 * @param int<min, 0> $a
 * @param int<min, 0> $b
 */
function bothUnboundedMin(int $a, int $b): void
{
	random_int($a, $b);
}

/**
 * @param int<0, max> $min
 * @param int<0, 10> $max
 */
function unboundedMinBoundedMax(int $min, int $max): void
{
	random_int($min, $max);
}

/**
 * @param int<0, 10> $min
 * @param int<min, 10> $max
 */
function boundedMinUnboundedMax(int $min, int $max): void
{
	random_int($min, $max);
}

/**
 * @param int<5, max> $min
 * @param int<min, 3> $max
 */
function unboundedButDefinitelyWrong(int $min, int $max): void
{
	random_int($min, $max); // error - max <= 3 < 5 <= min
}

/** @param positive-int $positiveInt */
function positiveInt(int $int, int $positiveInt): void
{
	random_int($int, $int);
	random_int($positiveInt, $positiveInt);
}
