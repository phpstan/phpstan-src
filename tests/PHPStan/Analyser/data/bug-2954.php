<?php

namespace Analyser\Bug2954;

use function PHPStan\Analyser\assertType;

function (int $x) {
	if ($x === 0) return;
	assertType('int<min, -1>|int<1, max>', $x);

	$x++;
	assertType('int', $x);
};

function (int $x) {
	if ($x === 0) return;
	assertType('int<min, -1>|int<1, max>', $x);

	++$x;
	assertType('int', $x);
};

function (int $x) {
	if ($x === 0) return;
	assertType('int<min, -1>|int<1, max>', $x);

	$x--;
	assertType('int', $x);
};

function (int $x) {
	if ($x === 0) return;
	assertType('int<min, -1>|int<1, max>', $x);

	--$x;
	assertType('int', $x);
};
