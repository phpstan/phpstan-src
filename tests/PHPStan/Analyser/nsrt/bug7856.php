<?php

namespace Bug7856;

use function PHPStan\Testing\assertType;

function doFoo() {
	$intervals = ['+1week', '+1months', '+6months', '+17months'];
	$periodEnd = new DateTimeImmutable('-1year');
	$endDate = new DateTimeImmutable('+1year');

	do {
		assertType("list<literal-string&lowercase-string&non-falsy-string>", $intervals);
		$periodEnd = $periodEnd->modify(array_shift($intervals));
	} while (count($intervals) > 0 && $periodEnd->format('U') < $endDate);
}
