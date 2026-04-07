<?php // lint >= 8.3

declare(strict_types = 1);

namespace Bug10862Php83;

use function PHPStan\Testing\assertType;

// PHP 8.3+: negative keys always affect auto-index (both imperative and literal)

// Imperative assignment
function () {
	$a = [];
	$a[-4] = 1;
	$a[] = 2;

	assertType('array{-4: 1, -3: 2}', $a);
};

function () {
	$a = [];
	$a[-1] = 'x';
	$a[] = 'y';

	assertType("array{-1: 'x', 0: 'y'}", $a);
};

function () {
	$a = [];
	$a[-10] = 'a';
	$a[-5] = 'b';
	$a[] = 'c';

	assertType("array{-10: 'a', -5: 'b', -4: 'c'}", $a);
};

function () {
	$a = [];
	$a[-3] = 'a';
	$a[5] = 'b';
	$a[] = 'c';

	assertType("array{-3: 'a', 5: 'b', 6: 'c'}", $a);
};

// Array literal
function () {
	$a = [-4 => 1];
	$a[] = 2;

	assertType('array{-4: 1, -3: 2}', $a);
};

function () {
	$a = [-10 => 'a', -5 => 'b'];
	$a[] = 'c';

	assertType("array{-10: 'a', -5: 'b', -4: 'c'}", $a);
};
