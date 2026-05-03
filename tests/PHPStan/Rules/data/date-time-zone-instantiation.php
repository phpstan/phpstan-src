<?php
namespace DateTimeZoneInstantiation;

// invalid timezone
new \DateTimeZone('invalid');

// valid timezones
new \DateTimeZone('Europe/Prague');
new \DateTimeZone('UTC');
new \DateTimeZone('America/New_York');
new \DateTimeZone('+02:00');

// empty string is invalid
new \DateTimeZone('');

// non-constant string - should not report
function foo(string $timezone): void {
	new \DateTimeZone($timezone);
}

// constant string via variable
$test = 'invalid';
new \DateTimeZone($test);

/**
 * @param 'Not/ATimezone' $tz2
 */
function bar(string $tz, string $tz2): void {
	new \DateTimeZone($tz);
	new \DateTimeZone($tz2);
}
