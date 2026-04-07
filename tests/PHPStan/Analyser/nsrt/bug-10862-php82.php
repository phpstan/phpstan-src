<?php // lint <= 8.2

declare(strict_types = 1);

namespace Bug10862Php82;

use function PHPStan\Testing\assertType;

// PHP <= 8.2: imperative assignment with negative keys does not affect auto-index

function () {
	$a = [];
	$a[-4] = 1;
	$a[] = 2;

	assertType('array{-4: 1, 0: 2}', $a);
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

	assertType("array{-10: 'a', -5: 'b', 0: 'c'}", $a);
};

function () {
	$a = [];
	$a[-3] = 'a';
	$a[5] = 'b';
	$a[] = 'c';

	assertType("array{-3: 'a', 5: 'b', 6: 'c'}", $a);
};
