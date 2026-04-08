<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10862Php80;

use function PHPStan\Testing\assertType;

// PHP 8.0+: array literal with negative keys updates auto-index

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

// Non-empty string-key array: negative key should update auto-index
function () {
	$a = ['foo' => 'bar'];
	$a[-5] = 'x';
	$a[] = 'y';

	assertType("array{foo: 'bar', -5: 'x', -4: 'y'}", $a);
};
