<?php

namespace DateIntervalFormat;

use DateInterval;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param string           $string
	 * @param non-empty-string $nonEmptyString
	 * @param '%Y'|'%D'        $unionString1
	 * @param '%Y'|'%y'        $unionString2
	 *
	 * @return void
	 */
	public function test(
		DateInterval $dateInterval,
		string $string,
		string $nonEmptyString,
		string $unionString1,
		string $unionString2,
	): void {
		assertType('string', $dateInterval->format($string));
		assertType('non-empty-string', $dateInterval->format($nonEmptyString));

		assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $dateInterval->format('%Y')); // '00'
		assertType('lowercase-string&non-empty-string&numeric-string&uppercase-string', $dateInterval->format('%y')); // '0'
		assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $dateInterval->format($unionString1));
		assertType('lowercase-string&non-empty-string&numeric-string&uppercase-string', $dateInterval->format($unionString2));

		assertType('non-falsy-string&uppercase-string', $dateInterval->format('%Y DAYS'));
		assertType('non-falsy-string&uppercase-string', $dateInterval->format($unionString1. ' DAYS'));

		assertType('lowercase-string&non-falsy-string', $dateInterval->format('%Y days'));
		assertType('lowercase-string&non-falsy-string', $dateInterval->format($unionString1. ' days'));
	}
}
