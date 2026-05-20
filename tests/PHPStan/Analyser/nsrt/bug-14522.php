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

/** @param int<-2, 2> $retryCount */
function maxWithBoundedRange(int $retryCount): void
{
	$result = max(0, $retryCount);
	assertType('int<0, 2>', $result);
	assertNativeType('int<0, max>', $result);
}
