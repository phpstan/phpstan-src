<?php

namespace Bug14428;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use function PHPStan\Testing\assertType;

function doImpure(DateInterval &$a): void {
}

function getDateTimeInterfacediff(DateTimeInterface $a, DateTimeInterface $b): void {
	$interval = $a->diff($b);
	assertType('int', $interval->days);
	doImpure($interval);
	assertType('int|false', $interval->days);
}

function getDateTimeImmutablediff(DateTimeImmutable $a, DateTimeImmutable $b): int {
	return $a->diff($b)->days;
}

function getDatetimediff(DateTime $a, DateTime $b): int {
	return $a->diff($b)->days;
}

function getDateDiffDays(DateTime $a, DateTime $b): int {
	return date_diff($a, $b)->days;
}
