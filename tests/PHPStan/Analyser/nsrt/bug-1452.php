<?php

namespace Bug1452;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use function PHPStan\Testing\assertType;

function doFoo(): void {
	$dateInterval = (new DateTimeImmutable('now -60 minutes'))->diff(new DateTimeImmutable('now'));
	assertType('lowercase-string&non-empty-string&numeric-string&uppercase-string', $dateInterval->format('%a'));
	assertType('float|int', $dateInterval->format('%a') * 60);
}

function doBar(DateTime $a, DateTime $b): void {
	$interval = $a->diff($b);
	assertType('lowercase-string&non-empty-string&numeric-string&uppercase-string', $interval->format('%a'));
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $interval->format('%R%a'));
}

function doBaz(DateTimeInterface $a, DateTimeInterface $b): void {
	$interval = $a->diff($b);
	assertType('lowercase-string&non-empty-string&numeric-string&uppercase-string', $interval->format('%a'));
}

function doDateDiff(DateTime $a, DateTime $b): void {
	$interval = date_diff($a, $b);
	assertType('lowercase-string&non-empty-string&numeric-string&uppercase-string', $interval->format('%a'));
}

function doPlainInterval(DateInterval $interval): void {
	assertType('lowercase-string&non-empty-string', $interval->format('%a'));
}

function doDateIntervalFormat(DateTime $a, DateTime $b): void {
	$interval = date_diff($a, $b);
	assertType('lowercase-string&non-empty-string&numeric-string&uppercase-string', date_interval_format($interval, '%a'));
}

function doDateIntervalFormatPlain(DateInterval $interval): void {
	assertType('lowercase-string&non-empty-string', date_interval_format($interval, '%a'));
}
