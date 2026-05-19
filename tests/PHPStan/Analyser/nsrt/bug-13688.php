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

function directTruthy(string $s): void
{
	if (strlen($s)) {
		assertType('non-empty-string', $s);
	} else {
		assertType("''", $s);
	}
	assertType('string', $s);
}

function directNegation(string $s): void
{
	if (!strlen($s)) {
		assertType("''", $s);
	} else {
		assertType('non-empty-string', $s);
	}
	assertType('string', $s);
}

function mbStrlenTruthy(string $s): void
{
	if (mb_strlen($s)) {
		assertType('non-empty-string', $s);
	} else {
		assertType("''", $s);
	}
}

function mbStrlenNegation(string $s): void
{
	if (!mb_strlen($s)) {
		assertType("''", $s);
	} else {
		assertType('non-empty-string', $s);
	}
}

function booleanAnd(string $a, string $b): void
{
	if (strlen($a) && strlen($b)) {
		assertType('non-empty-string', $a);
		assertType('non-empty-string', $b);
	}
}

function booleanOr(string $a, string $b): void
{
	if (!strlen($a) || !strlen($b)) {
		return;
	}
	assertType('non-empty-string', $a);
	assertType('non-empty-string', $b);
}

function ternary(string $s): void
{
	$result = strlen($s) ? 'non-empty' : 'empty';
	assertType("'empty'|'non-empty'", $result);
}

/** @param int|string $intOrString */
function nonStringInput($intOrString): void
{
	if (strlen($intOrString)) {
		assertType('int|string', $intOrString);
	}
}

/** @param float|string $mixedFloat */
function nonStringInputFloat($mixedFloat): void
{
	if (strlen($mixedFloat)) {
		assertType('float|string', $mixedFloat);
	}
}

/** @param bool|string $mixedBool */
function nonStringInputBool($mixedBool): void
{
	if (strlen($mixedBool)) {
		assertType('bool|string', $mixedBool);
	}
}

function looseComparisonTrue(string $s): void
{
	if (strlen($s) == true) {
		assertType('non-empty-string', $s);
	} else {
		assertType("''", $s);
	}
}

function looseComparisonFalse(string $s): void
{
	if (strlen($s) == false) {
		assertType("''", $s);
	} else {
		assertType('non-empty-string', $s);
	}
}
