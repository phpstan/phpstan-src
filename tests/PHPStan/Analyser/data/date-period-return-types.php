<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;

$start = new DateTime('2012-07-01');
$interval = new DateInterval('P7D');
$end = new DateTime('2012-07-31');
$recurrences = 4;
$iso = 'R4/2012-07-01T00:00:00Z/P7D';

$datePeriod = new DatePeriod($start, $interval, $end);
assertType(\DateTimeInterface::class, $datePeriod->getEndDate());
assertType('null', $datePeriod->getRecurrences());

$datePeriod = new DatePeriod($start, $interval, $recurrences);
assertType('null', $datePeriod->getEndDate());
assertType('int', $datePeriod->getRecurrences());

$datePeriod = new DatePeriod($iso);
assertType('null', $datePeriod->getEndDate());
assertType('int', $datePeriod->getRecurrences());
