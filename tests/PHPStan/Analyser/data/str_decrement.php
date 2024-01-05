<?php declare(strict_types = 1);

namespace StrDecrementFunctionReturn;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $s
 */
function foo(string $s): void
{
	assertType('non-empty-string', str_decrement($s));
	assertType('*ERROR*', str_decrement(''));
	assertType('*ERROR*', str_decrement('0'));
	assertType('*ERROR*', str_decrement('0.0'));
	assertType('*ERROR*', str_decrement('1.0'));
	assertType('*ERROR*', str_decrement('a'));
	assertType('*ERROR*', str_decrement('A'));
	assertType('*ERROR*', str_decrement('='));
	assertType('*ERROR*', str_decrement('字'));
	assertType("'0'", str_decrement('1'));
	assertType("'8'", str_decrement('9'));
	assertType("'9'", str_decrement('10'));
	assertType("'10'", str_decrement('11'));
	assertType("'1d'", str_decrement('1e'));
	assertType("'1e'", str_decrement('1f'));
	assertType("'18'", str_decrement('19'));
	assertType("'19'", str_decrement('20'));
	assertType("'z'", str_decrement('1a'));
	assertType("'1f0'", str_decrement('1f1'));
	assertType("'x'", str_decrement('y'));
	assertType("'y'", str_decrement('z'));
	assertType("'y9'", str_decrement('z0'));
	assertType("'z'", str_decrement('aa'));
	assertType("'zy'", str_decrement('zz'));
}

/**
 * @param 'b'|'1' $s1
 * @param 1|string $s2
 */
function union($s1, $s2): void
{
	assertType("'0'|'a'", str_decrement($s1));
	assertType('non-empty-string', str_decrement($s2));
}

/**
 * @param 'b'|'' $s
 */
function unionContainsInvalidInput($s): void
{
	assertType("'a'", str_decrement($s));
}
