<?php

namespace Analyser\Bug2954;

use function PHPStan\Analyser\assertType;

function (int $x) {
	if ($x === 0) return;
	assertType('int<1, max>|int<min, -1>', $x);

	$x++;
	assertType('int', $x);
};

function (int $x) {
	if ($x === 0) return;
	assertType('int<1, max>|int<min, -1>', $x);

	++$x;
	assertType('int', $x);
};

function (int $x) {
	if ($x === 0) return;
	assertType('int<1, max>|int<min, -1>', $x);

	$x--;
	assertType('int', $x);
};

function (int $x) {
	if ($x === 0) return;
	assertType('int<1, max>|int<min, -1>', $x);

	--$x;
	assertType('int', $x);
};
