<?php // lint >= 8.3

declare(strict_types = 1);

namespace Bug10862Php83;

use function PHPStan\Testing\assertType;

function () {
	$a = [];
	$a[-4] = 1;
	$a[] = 2;

	assertType('array{-4: 1, -3: 2}', $a); // PHP 8.3+: next key after -4 is -3
	assertType('array{-4, -3}', array_keys($a));
};

function () {
	$a = [];
	$a[-1] = 'x';
	$a[] = 'y';

	assertType("array{-1: 'x', 0: 'y'}", $a); // PHP 8.3+: next key after -1 is 0
	assertType('array{-1, 0}', array_keys($a));
};

function () {
	$a = [];
	$a[-10] = 'a';
	$a[-5] = 'b';
	$a[] = 'c';

	assertType("array{-10: 'a', -5: 'b', -4: 'c'}", $a); // PHP 8.3+: next key after max(-10,-5)=-5 is -4
	assertType('array{-10, -5, -4}', array_keys($a));
};

function () {
	$a = [];
	$a[-3] = 'a';
	$a[5] = 'b';
	$a[] = 'c';

	assertType("array{-3: 'a', 5: 'b', 6: 'c'}", $a); // positive key dominates
	assertType('array{-3, 5, 6}', array_keys($a));
};
