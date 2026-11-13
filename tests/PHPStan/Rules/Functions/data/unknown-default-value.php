<?php // lint >= 8.1

namespace UnknownDefaultValue;

function functions(array $a, callable $open, callable $read): void
{
	array_keys($a, strict: true);
	mt_rand(max: 5);
	session_set_save_handler($open, read: $read);

	array_keys($a, 1, strict: true);
	mt_rand(min: 1, max: 5);
	array_keys(array: $a);
}

function methods(\IntlCalendar $calendar, \DateTimeImmutable $start, \DateTimeImmutable $end): void
{
	$calendar->set(2020, 1, hour: 5);
	new \DatePeriod($start, end: $end);

	$calendar->set(2020, 1, 1, hour: 5);
}

function closures(array $a): void
{
	$keys = array_keys(...);
	$keys($a, strict: true);
	(mt_rand(...))(max: 5);

	$keys($a, 1, strict: true);
}
