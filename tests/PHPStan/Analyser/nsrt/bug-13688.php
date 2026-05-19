<?php declare(strict_types = 1);

namespace Bug13688;

use function PHPStan\Testing\assertType;

function narrowThroughVariable(): void
{
	$inputs = ['', ':'];

	foreach ($inputs as $input) {
		$inputLen = \strlen($input);

		assertType("''|':'", $input);
		assertType('int<0, 1>', $inputLen);

		if ($inputLen > 0) {
			assertType("':'", $input);
			assertType('1', $inputLen);
			assertType('0', $inputLen - 1);
		}
	}
}

function narrowThroughVariableInBooleanAnd(): void
{
	$inputs = ['', ':'];

	foreach ($inputs as $input) {
		$inputLen = \strlen($input);
		$hasTrailingColon = $inputLen > 0 && $input[$inputLen - 1] === ':';
	}
}

function narrowNonFalsy(): void
{
	/** @var string $str */
	$str = 'x';
	$len = \strlen($str);

	if ($len >= 2) {
		assertType('non-falsy-string', $str);
	}

	if ($len > 1) {
		assertType('non-falsy-string', $str);
	}

	if ($len >= 1) {
		assertType('non-empty-string', $str);
	}

	if ($len > 0) {
		assertType('non-empty-string', $str);
	}
}

function narrowMbStrlen(): void
{
	/** @var string $str */
	$str = 'x';
	$len = \mb_strlen($str);

	if ($len > 0) {
		assertType('non-empty-string', $str);
	}

	if ($len >= 2) {
		assertType('non-falsy-string', $str);
	}
}

function narrowNotEquals(): void
{
	/** @var string $str */
	$str = 'x';
	$len = \strlen($str);

	if ($len !== 0) {
		assertType('non-empty-string', $str);
	}

	if ($len === 0) {
		assertType('string', $str);
	}
}

function noNarrowAfterReassignment(): void
{
	/** @var string $str */
	$str = 'x';
	$len = \strlen($str);
	$len = 5;

	if ($len > 0) {
		assertType('string', $str);
	}
}
