<?php declare(strict_types=1);

use function PHPStan\Testing\assertType;

$start = new DateTime('2012-07-01');
$interval = new DateInterval('P7D');
$end = new DateTime('2012-07-31');
$recurrences = 4;
$iso = 'R4/2012-07-01T00:00:00Z/P7D';

$datePeriodList = [];

$datePeriod = new DatePeriod($start, $interval, $end);
assertType(\DateTimeInterface::class, $datePeriod->getEndDate());
assertType('null', $datePeriod->getRecurrences());
$datePeriodList[] = $datePeriod;

$datePeriod = new DatePeriod($start, $interval, $recurrences);
assertType('null', $datePeriod->getEndDate());
assertType('int', $datePeriod->getRecurrences());
$datePeriodList[] = $datePeriod;

$datePeriod = new DatePeriod($iso);
assertType('null', $datePeriod->getEndDate());
assertType('int', $datePeriod->getRecurrences());
$datePeriodList[] = $datePeriod;

/** @var DatePeriod $datePeriod */
$datePeriod = $datePeriodList[random_int(0, 2)];
assertType(\DateTimeInterface::class . '|null', $datePeriod->getEndDate());
assertType('int|null', $datePeriod->getRecurrences());
