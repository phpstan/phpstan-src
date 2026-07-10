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
	assertType('array{int<0, 59>, int<0, 59>, int<0, 23>, int<1, 31>, int<0, 11>, int, int<0, 6>, int<0, 365>, int}', localtime());
	assertType('array{int<0, 59>, int<0, 59>, int<0, 23>, int<1, 31>, int<0, 11>, int, int<0, 6>, int<0, 365>, int}', localtime($timestamp));
	assertType('array{int<0, 59>, int<0, 59>, int<0, 23>, int<1, 31>, int<0, 11>, int, int<0, 6>, int<0, 365>, int}', localtime($timestamp, false));
	assertType('array{tm_sec: int<0, 59>, tm_min: int<0, 59>, tm_hour: int<0, 23>, tm_mday: int<1, 31>, tm_mon: int<0, 11>, tm_year: int, tm_wday: int<0, 6>, tm_yday: int<0, 365>, tm_isdst: int}', localtime($timestamp, true));
	assertType('array{int<0, 59>, int<0, 59>, int<0, 23>, int<1, 31>, int<0, 11>, int, int<0, 6>, int<0, 365>, int}|array{tm_sec: int<0, 59>, tm_min: int<0, 59>, tm_hour: int<0, 23>, tm_mday: int<1, 31>, tm_mon: int<0, 11>, tm_year: int, tm_wday: int<0, 6>, tm_yday: int<0, 365>, tm_isdst: int}', localtime($timestamp, $assoc));
};
