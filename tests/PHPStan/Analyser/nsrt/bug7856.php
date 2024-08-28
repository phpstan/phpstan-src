<?php

namespace Bug7856;

use function PHPStan\Testing\assertType;

function doFoo() {
	$intervals = ['+1week', '+1months', '+6months', '+17months'];
	$periodEnd = new DateTimeImmutable('-1year');
	$endDate = new DateTimeImmutable('+1year');

	do {
		assertType("array{'+1week', '+1months', '+6months', '+17months'}|array{0: literal-string&non-falsy-string, 1?: literal-string&non-falsy-string, 2?: '+17months'}", $intervals);
		$periodEnd = $periodEnd->modify(array_shift($intervals));
	} while (count($intervals) > 0 && $periodEnd->format('U') < $endDate);
}
