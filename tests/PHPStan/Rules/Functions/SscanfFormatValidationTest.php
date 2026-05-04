<?php declare(strict_types = 1);

/*
 * This file is part of PHPStan - PHP Static Analysis Tool.
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2016 Ondřej Mirtes
 * Copyright (c) 2025 PHPStan s.r.o.
 *
 * This file contains code ported from PHP (ext/standard/scanf.c):
 *   Copyright © The PHP Group and Contributors.
 *   Original author: Clayton Collie <clcollie@mindspring.com>
 *   SPDX-License-Identifier: BSD-3-Clause
 *   See https://www.php.net/license/
 *
 * The validateFormatC() and specifierReturnTypeC() methods are PHP ports
 * of ValidateFormat() and the per-specifier dispatch in php_sscanf_internal()
 * from php-src, used under the BSD-3-Clause license.
 *
 * Source: ext/standard/scanf.c
 * Commit: 5164621436e8eb84952c9fdb4c931cd9a50754d9
 * Blob:   980009c30640a0dee171d11155a8d7ae09f174ff
 */

namespace PHPStan\Rules\Functions;

use Override;
use PHPStan\Php\PhpVersion;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * Comprehensive test comparing PHPStan's sscanf format string parsing
 * against the authoritative C implementation (ValidateFormat in ext/standard/scanf.c).
 *
 * Algorithms compared:
 *  1. C reference (ValidateFormat ported to PHP)
 *  2. PrintfHelper::getScanfPlaceholdersCount (parameter count rule)
 *  3. SscanfFunctionDynamicReturnTypeExtension regex (return type inference)
 *  4. Runtime sscanf with matching input (PHP's own implementation)
 */
class SscanfFormatValidationTest extends PHPStanTestCase
{

	private PrintfHelper $printfHelper;

	#[Override]
	protected function setUp(): void
	{
		$this->printfHelper = new PrintfHelper(new PhpVersion(PHP_VERSION_ID));
	}

	// ---------------------------------------------------------------
	// PHP port of PHPAPI int ValidateFormat(char *format, int numVars, int *totalSubs)
	// from ext/standard/scanf.c (php-src).
	//
	// This function iterates the format string character by character,
	// exactly replicating the C logic:
	//   - C strings terminate at NUL (\0); this port checks explicitly
	//   - %% is a literal percent, not a placeholder
	//   - %* is assignment suppression (placeholder parsed but not counted)
	//   - Digits after % may be either XPG3 positional (%n$) or a width
	//   - Size modifiers (l, L, h) are consumed and ignored
	//   - The switch on the specifier character is the definitive list
	//     of valid scanf specifiers: n d D i o x X u f e E g s c [
	//   - Character sets ([...]) handle ] as the first character and ^
	//
	// Returns:
	//   count = number of capturing (non-suppressed) placeholders
	//   error = error message string on failure, null on success
	// ---------------------------------------------------------------

	/**
	 * @return array{count: int|null, error: string|null}
	 */
	public static function validateFormatC(string $format): array
	{
		$len = strlen($format);
		$pos = 0;
		$objIndex = 0;
		$gotXpg = false;
		$gotSequential = false;
		$xpgSize = 0;

		while ($pos < $len && $format[$pos] !== "\0") {
			$ch = $format[$pos];
			$pos++;

			if ($ch !== '%') {
				continue;
			}

			if ($pos >= $len || $format[$pos] === "\0") {
				return ['count' => null, 'error' => 'Bad scan conversion character ""'];
			}

			$ch = $format[$pos];
			$pos++;

			if ($ch === '%') {
				continue;
			}

			$suppress = false;

			if ($ch === '*') {
				$suppress = true;
				if ($pos >= $len || $format[$pos] === "\0") {
					return ['count' => null, 'error' => 'Bad scan conversion character ""'];
				}
				$ch = $format[$pos];
				$pos++;
			} elseif (ctype_digit($ch)) {
				$numStart = $pos - 1;
				while ($pos < $len && ctype_digit($format[$pos])) {
					$pos++;
				}
				if ($pos < $len && $format[$pos] === '$') {
					$value = (int) substr($format, $numStart, $pos - $numStart);
					$pos++;
					$gotXpg = true;
					if ($gotSequential) {
						return ['count' => null, 'error' => 'cannot mix "%" and "%n$" conversion specifiers'];
					}
					if ($value < 1 || $value > 255) {
						return ['count' => null, 'error' => '"%n$" argument index out of range'];
					}
					$xpgSize = max($xpgSize, $value);
					$objIndex = $value - 1;
					if ($pos >= $len || $format[$pos] === "\0") {
						return ['count' => null, 'error' => 'Bad scan conversion character ""'];
					}
					$ch = $format[$pos];
					$pos++;
				} else {
					$pos = $numStart + 1;
					$gotSequential = true;
					if ($gotXpg) {
						return ['count' => null, 'error' => 'cannot mix "%" and "%n$" conversion specifiers'];
					}
				}
			} else {
				$gotSequential = true;
				if ($gotXpg) {
					return ['count' => null, 'error' => 'cannot mix "%" and "%n$" conversion specifiers'];
				}
			}

			if (ctype_digit($ch)) {
				while ($pos < $len && ctype_digit($format[$pos])) {
					$pos++;
				}
				if ($pos >= $len || $format[$pos] === "\0") {
					return ['count' => null, 'error' => 'Bad scan conversion character ""'];
				}
				$ch = $format[$pos];
				$pos++;
			}

			if ($ch === 'l' || $ch === 'L' || $ch === 'h') {
				if ($pos >= $len || $format[$pos] === "\0") {
					return ['count' => null, 'error' => 'Bad scan conversion character ""'];
				}
				$ch = $format[$pos];
				$pos++;
			}

			switch ($ch) {
				case 'n':
				case 'd':
				case 'D':
				case 'i':
				case 'o':
				case 'x':
				case 'X':
				case 'u':
				case 'f':
				case 'e':
				case 'E':
				case 'g':
				case 's':
				case 'c':
					break;

				case '[':
					if ($pos >= $len || $format[$pos] === "\0") {
						return ['count' => null, 'error' => 'Unmatched [ in format string'];
					}
					$setCh = $format[$pos];
					$pos++;
					if ($setCh === '^') {
						if ($pos >= $len || $format[$pos] === "\0") {
							return ['count' => null, 'error' => 'Unmatched [ in format string'];
						}
						$setCh = $format[$pos];
						$pos++;
					}
					if ($setCh === ']') {
						if ($pos >= $len || $format[$pos] === "\0") {
							return ['count' => null, 'error' => 'Unmatched [ in format string'];
						}
						$setCh = $format[$pos];
						$pos++;
					}
					while ($setCh !== ']') {
						if ($pos >= $len || $format[$pos] === "\0") {
							return ['count' => null, 'error' => 'Unmatched [ in format string'];
						}
						$setCh = $format[$pos];
						$pos++;
					}
					break;

				default:
					return ['count' => null, 'error' => sprintf('Bad scan conversion character "%s"', $ch)];
			}

			if (!$suppress) {
				$objIndex++;
			}
		}

		if ($xpgSize > 0) {
			return ['count' => $xpgSize, 'error' => null];
		}

		return ['count' => $objIndex, 'error' => null];
	}

	// ---------------------------------------------------------------
	// PHP port of per-specifier return type logic from php_sscanf_internal.
	//
	// Derived from the switch(op) dispatch in the C source:
	//   %n        → add_index_long           → int
	//   %d %D %i  → ZEND_STRTOL (signed)    → int
	//   %o %x %X  → ZEND_STRTOL (signed)    → int
	//   %u        → ZEND_STRTOUL (unsigned)  → int|string
	//                (string when value > PHP_INT_MAX, via snprintf)
	//   %f %e %E %g → zend_strtod            → float
	//   %s %c     → string copy              → string
	//   %[...]    → CharSet matching          → string
	// ---------------------------------------------------------------

	/** @return 'int'|'int|string'|'float'|'string' */
	public static function specifierReturnTypeC(string $specifier): string
	{
		switch ($specifier) {
			case 'n':
			case 'd':
			case 'D':
			case 'i':
			case 'o':
			case 'x':
			case 'X':
				return 'int';
			case 'u':
				return 'int|string';
			case 'f':
			case 'e':
			case 'E':
			case 'g':
				return 'float';
			default:
				return 'string'; // s, c, [...]
		}
	}

	/**
	 * Extracts specifier types using the same regex and mapping logic
	 * as SscanfFunctionDynamicReturnTypeExtension::getTypeFromFunctionCall.
	 *
	 * @return array{count: int, types: list<string>}
	 */
	public static function extensionRegexParse(string $format): array
	{
		$beforeNul = strstr($format, "\0", true);
		if ($beforeNul !== false) {
			$format = $beforeNul;
		}

		$types = [];
		if (preg_match_all('/%(\d*)(\[[^\]]+\]|[cDdeEfginosuxX]{1})/', $format, $matches) > 0) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$specifier = $matches[2][$i];

				if (in_array($specifier, ['d', 'D', 'i', 'n', 'o', 'x', 'X'], true)) {
					$types[] = 'int';
				} elseif ($specifier === 'u') {
					$types[] = 'int|string';
				} elseif (in_array($specifier, ['e', 'E', 'f', 'g'], true)) {
					$types[] = 'float';
				} else {
					$types[] = 'string';
				}
			}
		}

		return ['count' => count($types), 'types' => $types];
	}

	/**
	 * Uses PHP runtime sscanf with crafted input to determine placeholder count.
	 *
	 * Note: sscanf('', $format) returns null for any format with specifiers
	 * (except %n) because the C code checks *string == '\0' before each
	 * conversion attempt and triggers underflow. A string with sufficient
	 * matching data is needed. This method uses a numeric string that
	 * satisfies most specifier types.
	 *
	 * @return array{count: int|null, error: string|null}
	 */
	public static function runtimeSscanfCount(string $format, string $input = '999 999 999 999 999 999 999 999 999 999'): array
	{
		try {
			$result = @sscanf($input, $format);
			if ($result === null) {
				return ['count' => null, 'error' => null];
			}

			return ['count' => count($result), 'error' => null];
		} catch (\ValueError $e) {
			return ['count' => null, 'error' => $e->getMessage()];
		}
	}

	/**
	 * All scanf format strings found in phpstan-src production and test code,
	 * plus hakre's review test cases, specifier coverage, and edge cases.
	 *
	 * Each entry documents:
	 *   format — the scanf format string
	 *   count  — expected number of capturing placeholders (from C ValidateFormat)
	 *   error  — error message from C ValidateFormat (null if valid)
	 *   types  — expected per-specifier return types (from C php_sscanf_internal)
	 *   runtimeInput — optional input string for runtime verification
	 *
	 * @return array<string, array{format: string, count: int|null, error: string|null, types: list<string>, runtimeInput?: string}>
	 */
	public static function allFormatStrings(): array
	{
		return [
			// =============================================
			// hakre's 5 test cases from review
			// =============================================
			'hakre #01: empty format' => [
				'format' => '',
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => '',
			],
			'hakre #02: lone percent' => [
				'format' => '%',
				'count' => null,
				'error' => 'Bad scan conversion character ""',
				'types' => [],
			],
			'hakre #03: %n specifier' => [
				'format' => '%n',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => 'hello',
			],
			'hakre #04: %% literal' => [
				'format' => '%%',
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => '%',
			],
			'hakre #05: unmatched [' => [
				'format' => '%[',
				'count' => null,
				'error' => 'Unmatched [ in format string',
				'types' => [],
			],

			// =============================================
			// All 15 specifiers from ValidateFormat switch
			// (the definitive set from ext/standard/scanf.c)
			// =============================================
			'spec %n (chars consumed)' => [
				'format' => '%n',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => 'hello',
			],
			'spec %d (signed decimal)' => [
				'format' => '%d',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '42',
			],
			'spec %D (alias for %d)' => [
				'format' => '%D',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '42',
			],
			'spec %i (base-detecting int)' => [
				'format' => '%i',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '0xff',
			],
			'spec %o (octal)' => [
				'format' => '%o',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '77',
			],
			'spec %x (hex lowercase)' => [
				'format' => '%x',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => 'ff',
			],
			'spec %X (hex uppercase)' => [
				'format' => '%X',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => 'FF',
			],
			'spec %u (unsigned)' => [
				'format' => '%u',
				'count' => 1,
				'error' => null,
				'types' => ['int|string'],
				'runtimeInput' => '42',
			],
			'spec %f (float)' => [
				'format' => '%f',
				'count' => 1,
				'error' => null,
				'types' => ['float'],
				'runtimeInput' => '3.14',
			],
			'spec %e (scientific)' => [
				'format' => '%e',
				'count' => 1,
				'error' => null,
				'types' => ['float'],
				'runtimeInput' => '1.5e2',
			],
			'spec %E (scientific uc)' => [
				'format' => '%E',
				'count' => 1,
				'error' => null,
				'types' => ['float'],
				'runtimeInput' => '1.5E2',
			],
			'spec %g (general float)' => [
				'format' => '%g',
				'count' => 1,
				'error' => null,
				'types' => ['float'],
				'runtimeInput' => '1.5',
			],
			'spec %s (string)' => [
				'format' => '%s',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'hello',
			],
			'spec %c (character)' => [
				'format' => '%c',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'x',
			],
			'spec %[a-z] (char class)' => [
				'format' => '%[a-z]',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'hello',
			],
			'spec %[^/] (negated class)' => [
				'format' => '%[^/]',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'hello',
			],

			// =============================================
			// Assignment suppression (%*)
			// =============================================
			'suppress %*d' => [
				'format' => '%*d',
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => '42',
			],
			'suppress %*s' => [
				'format' => '%*s',
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => 'hello',
			],
			'suppress %*[a-z]' => [
				'format' => '%*[a-z]',
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => 'hello',
			],
			'suppress mixed: %*d %d' => [
				'format' => '%*d %d',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '10 20',
			],

			// =============================================
			// Width specifiers
			// =============================================
			'width %0s' => [
				'format' => '%0s',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'hello',
			],
			'width %2x' => [
				'format' => '%2x',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => 'ff',
			],
			'width %20s' => [
				'format' => '%20s',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'hello',
			],

			// =============================================
			// NUL byte termination
			// =============================================
			"nul: %d\\0%d" => [
				'format' => "%d\0%d",
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '42',
			],
			"nul: %d %s\\0%d" => [
				'format' => "%d %s\0%d",
				'count' => 2,
				'error' => null,
				'types' => ['int', 'string'],
				'runtimeInput' => '42 hello',
			],
			"nul: \\0%d%s (nul at start)" => [
				'format' => "\0%d%s",
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => '',
			],

			// =============================================
			// Error cases
			// =============================================
			'error: %z (bad specifier)' => [
				'format' => '%z',
				'count' => null,
				'error' => 'Bad scan conversion character "z"',
				'types' => [],
			],
			'error: %b (bad specifier)' => [
				'format' => '%b',
				'count' => null,
				'error' => 'Bad scan conversion character "b"',
				'types' => [],
			],
			'error: %[abc (unmatched)' => [
				'format' => '%[abc',
				'count' => null,
				'error' => 'Unmatched [ in format string',
				'types' => [],
			],
			'error: %[^abc (unmatched)' => [
				'format' => '%[^abc',
				'count' => null,
				'error' => 'Unmatched [ in format string',
				'types' => [],
			],

			// =============================================
			// Production code format strings (src/)
			// =============================================
			'prod: RegexGroupParser {%d,%d}' => [
				'format' => '{%d,%d}',
				'count' => 2,
				'error' => null,
				'types' => ['int', 'int'],
				'runtimeInput' => '{10,20}',
			],
			'prod: RegexGroupParser {%d,}' => [
				'format' => '{%d,}',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '{10,}',
			],
			'prod: RegexGroupParser {%d}' => [
				'format' => '{%d}',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '{10}',
			],

			// =============================================
			// Test data format strings (tests/)
			// =============================================
			'test: sscanf.php %d-%d' => [
				'format' => '%d-%d',
				'count' => 2,
				'error' => null,
				'types' => ['int', 'int'],
				'runtimeInput' => '20-20',
			],
			'test: sscanf.php %s %d %d' => [
				'format' => '%s %d %d',
				'count' => 3,
				'error' => null,
				'types' => ['string', 'int', 'int'],
				'runtimeInput' => 'January 01 2000',
			],
			'test: sscanf.php %1s' => [
				'format' => '%1s',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'x',
			],
			'test: sscanf.php %2s' => [
				'format' => '%2s',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'xy',
			],
			'test: sscanf.php %2x%2x%2x' => [
				'format' => '%2x%2x%2x',
				'count' => 3,
				'error' => null,
				'types' => ['int', 'int', 'int'],
				'runtimeInput' => '00ccff',
			],
			'test: sscanf.php %*s %d' => [
				'format' => '%*s %d',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => 'skip 42',
			],
			'test: sscanf.php %*d %s' => [
				'format' => '%*d %s',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => '42 hello',
			],
			'test: sscanf.php %*[a-z]%d' => [
				'format' => '%*[a-z]%d',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => 'abc42',
			],
			'test: bug-7764 %[^/]/%[^/]/%s' => [
				'format' => '%[^/]/%[^/]/%s',
				'count' => 3,
				'error' => null,
				'types' => ['string', 'string', 'string'],
				'runtimeInput' => 'hello/world/foo',
			],
			'test: bug-7563 %[1234567890.]%s' => [
				'format' => '%[1234567890.]%s',
				'count' => 2,
				'error' => null,
				'types' => ['string', 'string'],
				'runtimeInput' => '123.45cm',
			],
			'test: bug-7563 %s [%d] at %[^:]:%d: %[^[]]' => [
				'format' => '%s [%d] at %[^:]:%d: %[^[]]',
				'count' => 5,
				'error' => null,
				'types' => ['string', 'int', 'string', 'int', 'string'],
				'runtimeInput' => 'Exception [1234] at /path:42: message',
			],
			'test: bug-7563 %[%[]' => [
				'format' => '%[%[]',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => '%[test',
			],
			'test: printf.php %d%d' => [
				'format' => '%d%d',
				'count' => 2,
				'error' => null,
				'types' => ['int', 'int'],
				'runtimeInput' => '12 34',
			],
			'test: printf.php %20[^,],%d' => [
				'format' => '%20[^,],%d',
				'count' => 2,
				'error' => null,
				'types' => ['string', 'int'],
				'runtimeInput' => 'hello,42',
			],
			"test: printf.php %20[^\\n]\\n%d" => [
				'format' => "%20[^\n]\n%d",
				'count' => 2,
				'error' => null,
				'types' => ['string', 'int'],
				'runtimeInput' => "hello\n42",
			],
			'test: printf.php %20[^abcde]a%d' => [
				'format' => '%20[^abcde]a%d',
				'count' => 2,
				'error' => null,
				'types' => ['string', 'int'],
				'runtimeInput' => 'xyz a42',
			],
			'test: printf.php %[A-Z]%d' => [
				'format' => '%[A-Z]%d',
				'count' => 2,
				'error' => null,
				'types' => ['string', 'int'],
				'runtimeInput' => 'ABC123',
			],
			'test: bug-10260 %*[a-z]_day_%s' => [
				'format' => '%*[a-z]_day_%s',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'appletone_day_1',
			],
			'test: bug-10260 %*s %*d %s' => [
				'format' => '%*s %*d %s',
				'count' => 1,
				'error' => null,
				'types' => ['string'],
				'runtimeInput' => 'foo 123 bar',
			],
			'test: bug-10260 %*[A-Z]%d' => [
				'format' => '%*[A-Z]%d',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => 'ABC123',
			],
			'test: bug-10260 %s %*s %d' => [
				'format' => '%s %*s %d',
				'count' => 2,
				'error' => null,
				'types' => ['string', 'int'],
				'runtimeInput' => 'hello world 42',
			],
			'test: bug-10260 %*d %*s' => [
				'format' => '%*d %*s',
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => '123 abc',
			],
			'test: param-out %d:%d:%d' => [
				'format' => '%d:%d:%d',
				'count' => 3,
				'error' => null,
				'types' => ['int', 'int', 'int'],
				'runtimeInput' => '10:05:03',
			],
			'test: param-out %s %s' => [
				'format' => '%s %s',
				'count' => 2,
				'error' => null,
				'types' => ['string', 'string'],
				'runtimeInput' => '42 psalm',
			],
			'test: bug-14567 %d%n' => [
				'format' => '%d%n',
				'count' => 2,
				'error' => null,
				'types' => ['int', 'int'],
				'runtimeInput' => '42',
			],

			// =============================================
			// Combination and edge cases
			// =============================================
			'combo: all integer specifiers' => [
				'format' => '%d %D %i %o %x %X %u %n',
				'count' => 8,
				'error' => null,
				'types' => ['int', 'int', 'int', 'int', 'int', 'int', 'int|string', 'int'],
				'runtimeInput' => '1 2 3 4 5 6 7 8',
			],
			'combo: all float specifiers' => [
				'format' => '%e %E %f %g',
				'count' => 4,
				'error' => null,
				'types' => ['float', 'float', 'float', 'float'],
				'runtimeInput' => '1.0 2.0 3.0 4.0',
			],
			'combo: mixed types' => [
				'format' => '%d %f %s',
				'count' => 3,
				'error' => null,
				'types' => ['int', 'float', 'string'],
				'runtimeInput' => '42 3.14 hello',
			],
			'edge: literal text only' => [
				'format' => 'hello world',
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => 'hello world',
			],
			'edge: %%%%' => [
				'format' => '%%%%',
				'count' => 0,
				'error' => null,
				'types' => [],
				'runtimeInput' => '%%',
			],
			'edge: %%%d' => [
				'format' => '%%%d',
				'count' => 1,
				'error' => null,
				'types' => ['int'],
				'runtimeInput' => '%42',
			],
		];
	}

	/**
	 * Subset of format strings where the extension regex has known limitations.
	 * These are excluded from the regex comparison test but documented here.
	 *
	 * The regex \[[^\]]+\] cannot match character sets where ] is the first
	 * character (e.g., %[]abc]), because [^\]]+ requires at least one non-]
	 * character. The C code handles this via special-casing: if the first
	 * character after [ (or [^) is ], it's treated as a literal member of
	 * the set rather than the closing bracket.
	 *
	 * @return array<string, array{format: string, count: int, types: list<string>}>
	 */
	public static function regexKnownLimitations(): array
	{
		// The regex \[[^\]]+\] cannot match %[]abc] where ] is the first
		// character in the set: [^\]]+ requires at least one non-] character
		// before the closing ], but in []abc] the ] comes immediately.
		// The C code special-cases this in BuildCharSet.
		//
		// %[^]abc] is NOT affected: [^\]]+ matches ^ (non-] char), then \]
		// matches the first ], yielding [^] as the parsed set — wrong parse,
		// but correct count (1 placeholder). So it's excluded from here.
		return [
			'%[]abc] — ] as first char in set' => [
				'format' => '%[]abc]',
				'count' => 1,
				'types' => ['string'],
			],
		];
	}

	// =============================================
	// Test methods
	// =============================================

	public function testValidateFormatCPort(): void
	{
		foreach (self::allFormatStrings() as $label => $entry) {
			$result = self::validateFormatC($entry['format']);

			if ($entry['error'] !== null) {
				self::assertNotNull(
					$result['error'],
					sprintf('[%s] C port should report error for %s', $label, self::esc($entry['format'])),
				);
			} else {
				self::assertNull(
					$result['error'],
					sprintf('[%s] C port unexpected error for %s: %s', $label, self::esc($entry['format']), $result['error'] ?? ''),
				);
				self::assertSame(
					$entry['count'],
					$result['count'],
					sprintf('[%s] C port count mismatch for %s', $label, self::esc($entry['format'])),
				);
			}
		}
	}

	#[RequiresPhp('>= 8.0')]
	public function testValidateFormatCPortMatchesRuntime(): void
	{

		foreach (self::allFormatStrings() as $label => $entry) {
			$cResult = self::validateFormatC($entry['format']);

			if ($entry['error'] !== null) {
				// Runtime should also error
				$runtime = self::runtimeSscanfCount($entry['format']);
				self::assertNotNull(
					$runtime['error'],
					sprintf('[%s] Runtime should error for %s', $label, self::esc($entry['format'])),
				);
				continue;
			}

			// For valid formats with runtimeInput, verify count matches
			if (!isset($entry['runtimeInput'])) {
				continue;
			}
			$runtime = self::runtimeSscanfCount($entry['format'], $entry['runtimeInput']);
			self::assertNull($runtime['error'], sprintf('[%s] Runtime error: %s', $label, $runtime['error'] ?? ''));
			self::assertSame(
				$entry['count'],
				$runtime['count'],
				sprintf('[%s] Runtime count mismatch for %s with input %s', $label, self::esc($entry['format']), self::esc($entry['runtimeInput'])),
			);
		}
	}

	public function testPrintfHelperMatchesCReference(): void
	{
		foreach (self::allFormatStrings() as $label => $entry) {
			if ($entry['error'] !== null) {
				continue;
			}

			$count = $this->printfHelper->getScanfPlaceholdersCount($entry['format']);

			self::assertSame(
				$entry['count'],
				$count,
				sprintf('[%s] PrintfHelper count mismatch for %s', $label, self::esc($entry['format'])),
			);
		}
	}

	public function testExtensionRegexMatchesCReference(): void
	{
		$knownLimitations = array_map(
			static fn (array $e): string => $e['format'],
			self::regexKnownLimitations(),
		);

		foreach (self::allFormatStrings() as $label => $entry) {
			if ($entry['error'] !== null) {
				continue;
			}
			if (in_array($entry['format'], $knownLimitations, true)) {
				continue;
			}

			$result = self::extensionRegexParse($entry['format']);

			self::assertSame(
				$entry['count'],
				$result['count'],
				sprintf('[%s] Regex count mismatch for %s', $label, self::esc($entry['format'])),
			);
			self::assertSame(
				$entry['types'],
				$result['types'],
				sprintf('[%s] Regex types mismatch for %s', $label, self::esc($entry['format'])),
			);
		}
	}

	public function testExtensionRegexKnownLimitations(): void
	{
		foreach (self::regexKnownLimitations() as $label => $entry) {
			$result = self::extensionRegexParse($entry['format']);

			// Document the current (incorrect) behavior
			self::assertNotSame(
				$entry['count'],
				$result['count'],
				sprintf('[%s] Regex limitation appears to be fixed — move from regexKnownLimitations to allFormatStrings', $label),
			);
		}
	}

	public function testSpecifierReturnTypes(): void
	{
		$expected = [
			'n' => 'int', 'd' => 'int', 'D' => 'int', 'i' => 'int',
			'o' => 'int', 'x' => 'int', 'X' => 'int',
			'u' => 'int|string',
			'f' => 'float', 'e' => 'float', 'E' => 'float', 'g' => 'float',
			's' => 'string', 'c' => 'string',
		];

		foreach ($expected as $spec => $type) {
			self::assertSame($type, self::specifierReturnTypeC($spec), sprintf('%%%s', $spec));
		}
	}

	/**
	 * Cross-validate: C port, PrintfHelper, and Extension regex must agree on
	 * placeholder count for all valid, non-limitation format strings.
	 */
	public function testStaticAlgorithmsAgree(): void
	{
		$knownLimitations = array_map(
			static fn (array $e): string => $e['format'],
			self::regexKnownLimitations(),
		);

		$mismatches = [];

		foreach (self::allFormatStrings() as $label => $entry) {
			if ($entry['error'] !== null) {
				continue;
			}
			if (in_array($entry['format'], $knownLimitations, true)) {
				continue;
			}

			$cCount = self::validateFormatC($entry['format'])['count'];
			$helperCount = $this->printfHelper->getScanfPlaceholdersCount($entry['format']);
			$regexResult = self::extensionRegexParse($entry['format']);

			if ($cCount !== $helperCount || $cCount !== $regexResult['count']) {
				$mismatches[] = sprintf(
					'[%s] %s  C=%s  Helper=%s  Regex=%s',
					$label,
					self::esc($entry['format']),
					self::descCount($cCount),
					self::descCount($helperCount),
					self::descCount($regexResult['count']),
				);
			}
		}

		self::assertSame([], $mismatches, "Algorithm mismatches:\n" . implode("\n", $mismatches));
	}

	private static function esc(string $s): string
	{
		return '"' . addcslashes($s, "\0\n\r\t\"\\") . '"';
	}

	private static function descCount(int|null $c): string
	{
		return $c === null ? 'null' : (string) $c;
	}

}
