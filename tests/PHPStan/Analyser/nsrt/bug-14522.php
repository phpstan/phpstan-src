<?php declare(strict_types = 1);

namespace Bug14522;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertNativeType;

/**
 * @return int<1, max>
 */
function getBackoffTime(int $retryCount, int $maxBackoff): int
{
	$retryCount = max(0, $retryCount);
	$maxBackoff = max(1, $maxBackoff);

	$total = 0;
	for ($i = 0; $i <= $retryCount; ++$i) {
		$total += min(2 ** $i, $maxBackoff);
	}
	assertType('int<1, max>', $total);
	return $total;
}

function simpleForLoopAlwaysEnters(int $n): void
{
	$n = max(0, $n);
	$total = 0;
	for ($i = 0; $i <= $n; $i++) {
		$total++;
	}
	assertType('int<1, max>', $total);
}

function forLoopNeverEnters(): void
{
	$total = 0;
	for ($i = 0; $i < 0; $i++) {
		$total++;
	}
	assertType('0', $total);
}

function forLoopMaybeEnters(int $n): void
{
	$total = 0;
	for ($i = 0; $i < $n; $i++) {
		$total++;
	}
	assertType('int<0, max>', $total);
}

function whileLoopAlwaysEnters(int $n): void
{
	$n = max(0, $n);
	$i = 0;
	$total = 0;
	while ($i <= $n) {
		$total++;
		$i++;
	}
	assertType('int<1, max>', $total);
}

function whileLoopMaybeEnters(int $n): void
{
	$i = 0;
	$total = 0;
	while ($i < $n) {
		$total++;
		$i++;
	}
	assertType('int<0, max>', $total);
}
