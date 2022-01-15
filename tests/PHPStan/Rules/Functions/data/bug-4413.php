<?php

namespace Bug4413;

use DateTime;

/**
 * @param class-string<DateTime> $date
 */
function takesDate(string $date): void {}

function input(string $in): void {
	switch ($in) {
		case DateTime::class :
			takesDate($in);
			break;
		case \stdClass::class :
			takesDate($in);
			break;
	}
}
