<?php declare(strict_types = 1);

namespace Bug13688Nsrt;

use function PHPStan\Testing\assertType;

function doFoo(string $input): void
{
	$inputLen = strlen($input);
	assertType('string', $input);

	if ($inputLen > 0) {
		assertType('non-empty-string', $input);
	}
}

function doBar(string $s): void
{
	$len = strlen($s);
	if ($len) {
		assertType('non-empty-string', $s);
	} else {
		assertType("''", $s);
	}
}

/**
 * @param ''|':' $input
 */
function doBaz(string $input): void
{
	$inputLen = strlen($input);
	if ($inputLen > 0) {
		assertType("':'", $input);
		assertType('1', $inputLen);
	}
}
