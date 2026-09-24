<?php declare(strict_types = 1);

namespace ByRefWritebackNativeType;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

function offsetCapture(string $s): void
{
	if (! preg_match('/(a)(b)?/', $s, $m, PREG_OFFSET_CAPTURE)) {
		return;
	}

	assertType("array{0: array{non-falsy-string, int<-1, max>}, 1: array{'a', int<-1, max>}, 2?: array{'b', int<-1, max>}}", $m);
	assertNativeType("array{0: array{non-falsy-string, int<-1, max>}, 1: array{'a', int<-1, max>}, 2?: array{'b', int<-1, max>}}", $m);
}

function noFlags(string $s): void
{
	if (! preg_match('/(a)(b)?/', $s, $m)) {
		return;
	}

	assertType("array{0: non-falsy-string, 1: 'a', 2?: 'b'}", $m);
	assertNativeType("array{0: non-falsy-string, 1: 'a', 2?: 'b'}", $m);
}

/** @param-out string $v */
function paramOutContradictsDeclaration(int &$v): void
{
	$v = 'now a string';
}

function userlandParamOut(): void
{
	$x = 1;
	paramOutContradictsDeclaration($x);

	assertType('string', $x);
	assertNativeType('mixed', $x);
}
