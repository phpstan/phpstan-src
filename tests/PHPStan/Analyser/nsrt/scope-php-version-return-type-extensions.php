<?php

namespace ScopePhpVersionReturnTypeExtensions;

use function PHPStan\Testing\assertType;

function arrayFunctionsWithNonArray(string $s): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('*NEVER*', array_keys($s));
		assertType('*NEVER*', array_values($s));
		assertType('*NEVER*', array_flip($s));
		assertType('*NEVER*', array_reverse($s));
		assertType('*NEVER*', array_slice($s, 1));
	} else {
		assertType('null', array_keys($s));
		assertType('null', array_values($s));
		assertType('null', array_flip($s));
		assertType('null', array_reverse($s));
		assertType('null', array_slice($s, 1));
	}
}

/**
 * @param array<string, int> $arr
 */
function arrayChunkAndFill(array $arr): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('*NEVER*', array_chunk($arr, 0));
		assertType('*NEVER*', array_fill(0, -1, 'x'));
		assertType('*NEVER*', array_combine(['a'], []));
	} else {
		assertType('null', array_chunk($arr, 0));
		assertType('false', array_fill(0, -1, 'x'));
		assertType('false', array_combine(['a'], []));
	}
}

function substrAndStrSplit(string $s): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('\'\'', substr('abc', 10));
		assertType('*NEVER*', str_split($s, 0));
	} else {
		assertType('false', substr('abc', 10));
		assertType('false', str_split($s, 0));
	}

	if (PHP_VERSION_ID >= 80200) {
		assertType('array{}', str_split(''));
	} else {
		assertType('array{\'\'}', str_split(''));
	}
}

function roundAndHighlight(): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('*NEVER*', round());
	} else {
		assertType('null', round());
	}

	if (PHP_VERSION_ID >= 80400) {
		assertType('true', highlight_string('<?php'));
	} else {
		assertType('bool', highlight_string('<?php'));
	}
}

function countCharsAndHash(string $s): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('string', count_chars($s, 3));
		assertType('*NEVER*', hash('nope', $s));
	} else {
		assertType('(string|false)', count_chars($s, 3));
		assertType('false', hash('nope', $s));
	}
}

function mbSubstituteCharacterAndTriggerError(): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('true', mb_substitute_character(null));
		assertType('*NEVER*', trigger_error('foo', 12345));
	} else {
		assertType('false', mb_substitute_character(null));
		assertType('false', trigger_error('foo', 12345));
	}
}

function versionCompareAndMinMax(string $a, string $b): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('bool', version_compare($a, $b, 'nope'));
		assertType('*ERROR*', min([]));
	} else {
		assertType('(bool|null)', version_compare($a, $b, 'nope'));
		assertType('false', min([]));
	}
}

function dateTimeModify(\DateTime $dt): void
{
	if (PHP_VERSION_ID >= 80300) {
		assertType('*NEVER*', $dt->modify('nope'));
		assertType('*NEVER*', \DateInterval::createFromDateString('nope'));
	} else {
		assertType('false', $dt->modify('nope'));
		assertType('false', \DateInterval::createFromDateString('nope'));
	}
}

function bcMath(): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('*NEVER*', bcdiv('1', '0'));
	} else {
		assertType('null', bcdiv('1', '0'));
	}
}

function filterVarThrowOnFailure(string $s): void
{
	if (PHP_VERSION_ID >= 80500) {
		assertType('int', filter_var($s, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE));
	} else {
		assertType('int|false', filter_var($s, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE));
	}
}

function getClassWithoutArguments(): void
{
	if (PHP_VERSION_ID >= 80000) {
		assertType('*NEVER*', get_class());
	} else {
		assertType('false', get_class());
	}
}
