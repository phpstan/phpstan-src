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
