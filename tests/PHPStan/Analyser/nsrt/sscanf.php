<?php

namespace Sscanf;

use function PHPStan\Testing\assertType;

function foo(string $s) {
	assertType('int|null', sscanf('20-20', '%d-%d', $first, $second));
	assertType('array{int|null, int|null}|null', sscanf('20-20', '%d-%d'));
}

function sscanfFormatInference(string $s) {
	assertType('int|null', sscanf($s, $s, $first, $second));
	assertType('array|null', sscanf($s, $s));

	assertType('array{string|null}|null', sscanf($s, '%c'));
	assertType('array{int|null}|null', sscanf($s, '%d'));
	assertType('array{float|null}|null', sscanf($s, '%e'));
	assertType('array{float|null}|null', sscanf($s, '%E'));
	assertType('array{float|null}|null', sscanf($s, '%f'));
	assertType('array{int|null}|null', sscanf($s, '%o'));
	assertType('array{string|null}|null', sscanf($s, '%s'));
	assertType('array{int|null}|null', sscanf($s, '%u'));
	assertType('array{int|null}|null', sscanf($s, '%x'));

	$mandate = "January 01 2000";
	list($month, $day, $year) = sscanf($mandate, "%s %d %d");
	assertType('string|null', $month);
	assertType('int|null', $day);
	assertType('int|null', $year);
}

function fscanfFormatInference($r) {
	list($month, $day, $year) = fscanf($r, "%s %d %d");
	assertType('string|null', $month);
	assertType('int|null', $day);
	assertType('int|null', $year);
}

function fooo(string $s) {
	// %0s returns the whole string
	assertType('array{non-empty-string|null}|null', sscanf( "0" , "%0s"));
	assertType('array{non-empty-string|null}|null', sscanf( "123456" , "%0s"));

	assertType('array{non-empty-string|null}|null', sscanf( "123456" , "%1s"));
	assertType('array{non-falsy-string|null}|null', sscanf( "123456" , "%2s"));
	assertType('array{non-falsy-string|null}|null', sscanf( "123456" , "%3s"));

	assertType('array{int|null, int|null, int|null}|null', sscanf('00ccff', '%2x%2x%2x'));
}
