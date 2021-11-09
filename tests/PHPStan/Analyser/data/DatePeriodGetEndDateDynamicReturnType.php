<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;

$start = new DateTime('2012-07-01');
$interval = new DateInterval('P7D');
$end = new DateTime('2012-07-31');
$recurrences = 4;
$iso = 'R4/2012-07-01T00:00:00Z/P7D';

assertType('null', (new DatePeriod($start, $interval, $recurrences))->getEndDate());
assertType(\DateTimeInterface::class, (new DatePeriod($start, $interval, $end))->getEndDate());
assertType('null', (new DatePeriod($iso))->getEndDate());
