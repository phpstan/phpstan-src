<?php

namespace ArrayLiteralPlainVariableKey;

use function PHPStan\Testing\assertType;

function readAfterTheValue(): void
{
	$j = 0;
	assertType('array{1: 0}', [$j => $j++]);

	$k = 'a';
	assertType("array{b: 'b'}", [$k => ($k = 'b')]);

	$n = 'a';
	assertType("array{c: array{'c'}}", [$n => [$n = 'c']]);

	$l = 0;
	assertType("array{'x', 1}", [$l => 'x', ++$l => $l]);
}

function otherKeysAreReadInOrder(): void
{
	$i = 0;
	assertType('array{1}', [$i++ => $i]);

	$a = ['k' => 'a'];
	assertType("array{a: 'b'}", [$a['k'] => ($a['k'] = 'b')]);
}

function implicitIndexAfterPhpIntMax(): void
{
	$a = [9223372036854775807 => 1];
	assertType('array{9223372036854775807: 1}', $a);
}
