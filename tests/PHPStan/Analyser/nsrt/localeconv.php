<?php

namespace LocaleconvBug;

use function localeconv;
use function localtime;
use function PHPStan\Testing\assertType;

function (): void {
	$conv = localeconv();

	assertType('array{decimal_point: string, thousands_sep: string, int_curr_symbol: string, currency_symbol: string, mon_decimal_point: string, mon_thousands_sep: string, positive_sign: string, negative_sign: string, int_frac_digits: int, frac_digits: int, p_cs_precedes: int, p_sep_by_space: int, n_cs_precedes: int, n_sep_by_space: int, p_sign_posn: int, n_sign_posn: int, grouping: list<int>}', $conv);

	assertType('string', $conv['thousands_sep']);
	assertType('string', $conv['decimal_point']);
	assertType('int', $conv['frac_digits']);
	assertType('list<int>', $conv['grouping']);
};

function (int $timestamp, bool $assoc): void {
	assertType('array{int, int, int, int, int, int, int, int, int}', localtime());
	assertType('array{int, int, int, int, int, int, int, int, int}', localtime($timestamp));
	assertType('array{int, int, int, int, int, int, int, int, int}', localtime($timestamp, false));
	assertType('array{tm_sec: int, tm_min: int, tm_hour: int, tm_mday: int, tm_mon: int, tm_year: int, tm_wday: int, tm_yday: int, tm_isdst: int}', localtime($timestamp, true));
	assertType('array{int, int, int, int, int, int, int, int, int}|array{tm_sec: int, tm_min: int, tm_hour: int, tm_mday: int, tm_mon: int, tm_year: int, tm_wday: int, tm_yday: int, tm_isdst: int}', localtime($timestamp, $assoc));
};
