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
	assertNativeType('int<0, max>', $retryCount);
	$maxBackoff = max(1, $maxBackoff);
	assertNativeType('int<1, max>', $maxBackoff);

	$total = 0;
	for ($i = 0; $i <= $retryCount; ++$i) {
		$total += min(2 ** $i, $maxBackoff);
	}
	assertType('int<1, max>', $total);
	return $total;
}

function forLoopWithMaxAlwaysEnters(int $n): void
{
	$n = max(0, $n);
	assertNativeType('int<0, max>', $n);
	$total = 0;
	for ($i = 0; $i <= $n; $i++) {
		$total++;
	}
	assertType('int<1, max>', $total);
}

function whileLoopAlwaysEnters(int $n): void
{
	$n = max(0, $n);
	assertNativeType('int<0, max>', $n);
	$i = 0;
	$total = 0;
	while ($i <= $n) {
		$total++;
		$i++;
	}
	assertType('int<1, max>', $total);
}
