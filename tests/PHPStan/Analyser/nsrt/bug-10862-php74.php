<?php // lint < 8.0

declare(strict_types = 1);

namespace Bug10862Php74;

use function PHPStan\Testing\assertType;

// Pre-PHP 8.0: negative keys never affect auto-index

// Imperative assignment
function () {
	$a = [];
	$a[-4] = 1;
	$a[] = 2;

	assertType('array{-4: 1, 0: 2}', $a);
};

// Array literal
function () {
	$a = [-4 => 1];
	$a[] = 2;

	assertType('array{-4: 1, 0: 2}', $a);
};
