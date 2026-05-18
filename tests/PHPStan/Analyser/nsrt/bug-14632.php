<?php

namespace Bug14632;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use function PHPStan\Testing\assertType;

function testDateTimeInterface(DateTimeInterface $a, DateTimeInterface $b): void {
	$interval = $a->diff($b);
	assertType('DateInterval&object{days: int}', $interval);
	assertType('int', $interval->days);
}

function testDateTimeImmutable(DateTimeImmutable $a, DateTimeImmutable $b): void {
	$interval = $a->diff($b);
	assertType('DateInterval&object{days: int}', $interval);
	assertType('int', $interval->days);
}

function testDateTime(DateTime $a, DateTime $b): void {
	$interval = $a->diff($b);
	assertType('DateInterval&object{days: int}', $interval);
	assertType('int', $interval->days);
}
