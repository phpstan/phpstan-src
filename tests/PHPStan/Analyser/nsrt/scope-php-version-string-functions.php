<?php declare(strict_types = 1);

namespace ScopePhpVersionStringFunctions;

use function PHPStan\Testing\assertType;

class Foo
{

	public function substrOutOfRange(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType("''", substr('abc', 5));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', substr('abc', 5));
		}
	}

	public function substrAccessory(string $s): void
	{
		if ($s === '') {
			return;
		}

		if (PHP_VERSION_ID >= 80000) {
			assertType('non-empty-string', substr($s, -1));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('non-empty-string', substr($s, -1));
		}
	}

	public function strSplitEmptyString(): void
	{
		if (PHP_VERSION_ID >= 80200) {
			assertType('array{}', str_split(''));
		}

		if (PHP_VERSION_ID < 80200) {
			assertType("array{''}", str_split(''));
		}
	}

	public function strSplitInvalidLength(string $s): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', str_split($s, 0));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', str_split($s, 0));
		}
	}

	public function highlightString(string $s): void
	{
		if (PHP_VERSION_ID >= 80400) {
			assertType('true', highlight_string($s));
		}

		if (PHP_VERSION_ID < 80400) {
			assertType('bool', highlight_string($s));
		}
	}

	public function countChars(string $s): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('array<int, int>', count_chars($s));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('(array<int, int>|false)', count_chars($s));
		}
	}

	public function hashInvalidAlgorithm(string $s): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', hash('unknown-algo', $s));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', hash('unknown-algo', $s));
		}
	}

	public function roundOnArray(): void
	{
		$arr = [1, 2, 3];

		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', round($arr));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', round($arr));
		}
	}

	public function versionCompareInvalidOperator(string $a, string $b): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('bool', version_compare($a, $b, 'nope'));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('(bool|null)', version_compare($a, $b, 'nope'));
		}
	}

	public function triggerErrorInvalidLevel(string $message): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', trigger_error($message, E_USER_ERROR + 1));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', trigger_error($message, E_USER_ERROR + 1));
		}
	}

	public function bcMathDivByZero(string $a): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('*NEVER*', bcdiv($a, '0'));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('null', bcdiv($a, '0'));
		}
	}

	public function mbSubstituteCharacterNull(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			assertType('true', mb_substitute_character(null));
		}

		if (PHP_VERSION_ID < 80000) {
			assertType('false', mb_substitute_character(null));
		}
	}

}
