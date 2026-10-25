<?php declare(strict_types = 1);

namespace Bug15537;

use DateTimeImmutable;
use function PHPStan\Testing\assertType;

/**
 * @param 'am'|'pm'|'AM'|'PM' $meridiem
 */
function translate_meridiem( string $meridiem ): string {
	return $meridiem;
}

function test( DateTimeImmutable $datetime, int $timestamp ): void {
	echo translate_meridiem( $datetime->format( 'a' ) );
	echo translate_meridiem( date( 'A', $timestamp ) );

	assertType( "'am'|'pm'", $datetime->format( 'a' ) );
	assertType( "'AM'|'PM'", $datetime->format( 'A' ) );
	assertType( "'nd'|'rd'|'st'|'th'", $datetime->format( 'S' ) );
	assertType( "'Fri'|'Mon'|'Sat'|'Sun'|'Thu'|'Tue'|'Wed'", $datetime->format( 'D' ) );
	assertType( "'Friday'|'Monday'|'Saturday'|'Sunday'|'Thursday'|'Tuesday'|'Wednesday'", $datetime->format( 'l' ) );
	assertType( "'Apr'|'Aug'|'Dec'|'Feb'|'Jan'|'Jul'|'Jun'|'Mar'|'May'|'Nov'|'Oct'|'Sep'", $datetime->format( 'M' ) );
	assertType( "'April'|'August'|'December'|'February'|'January'|'July'|'June'|'March'|'May'|'November'|'October'|'September'", $datetime->format( 'F' ) );

	assertType( "'am'|'pm'", date( 'a', $timestamp ) );
	assertType( "'Friday'|'Monday'|'Saturday'|'Sunday'|'Thursday'|'Tuesday'|'Wednesday'", date_format( $datetime, 'l' ) );
}
