<?php declare(strict_types = 1);

namespace Bug13378;

use function PHPStan\Testing\assertType;

function formatMonthConcat(\DateTimeInterface $date, bool $short = false): string
{
	$month = $date->format('n');
	$prefix = $short ? 'SHORT_' : '';

	$formatted = $prefix . 'MONTH_' . $month;

	assertType("'MONTH_1'|'MONTH_10'|'MONTH_11'|'MONTH_12'|'MONTH_2'|'MONTH_3'|'MONTH_4'|'MONTH_5'|'MONTH_6'|'MONTH_7'|'MONTH_8'|'MONTH_9'|'SHORT_MONTH_1'|'SHORT_MONTH_10'|'SHORT_MONTH_11'|'SHORT_MONTH_12'|'SHORT_MONTH_2'|'SHORT_MONTH_3'|'SHORT_MONTH_4'|'SHORT_MONTH_5'|'SHORT_MONTH_6'|'SHORT_MONTH_7'|'SHORT_MONTH_8'|'SHORT_MONTH_9'", $formatted);

	return $formatted;
}

function formatMonthSprintf(\DateTimeInterface $date, bool $short = false): string
{
	$month = $date->format('n');
	$prefix = $short ? 'SHORT_' : '';

	$formatted = sprintf('%sMONTH_%s', $prefix, $month);

	assertType("'MONTH_1'|'MONTH_10'|'MONTH_11'|'MONTH_12'|'MONTH_2'|'MONTH_3'|'MONTH_4'|'MONTH_5'|'MONTH_6'|'MONTH_7'|'MONTH_8'|'MONTH_9'|'SHORT_MONTH_1'|'SHORT_MONTH_10'|'SHORT_MONTH_11'|'SHORT_MONTH_12'|'SHORT_MONTH_2'|'SHORT_MONTH_3'|'SHORT_MONTH_4'|'SHORT_MONTH_5'|'SHORT_MONTH_6'|'SHORT_MONTH_7'|'SHORT_MONTH_8'|'SHORT_MONTH_9'", $formatted);

	return $formatted;
}
