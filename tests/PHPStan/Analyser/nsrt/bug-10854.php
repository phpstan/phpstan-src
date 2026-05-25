<?php

namespace Bug10854;

use function PHPStan\Testing\assertType;

function listFromExplode(string $input): void
{
	list($a, $b) = explode('-', $input);
	assertType('string', $a);
	assertType('string|null', $b);
}

function shortListFromExplode(string $input): void
{
	[$a, $b] = explode('-', $input);
	assertType('string', $a);
	assertType('string|null', $b);
}

/**
 * @param list<string> $list
 */
function listFromGenericList(array $list): void
{
	[$a, $b] = $list;
	assertType('string|null', $a);
	assertType('string|null', $b);
}

/**
 * @param array<int, string> $arr
 */
function listFromGenericArray(array $arr): void
{
	[$a, $b] = $arr;
	assertType('string|null', $a);
	assertType('string|null', $b);
}

/**
 * @param non-empty-list<string> $list
 */
function listFromNonEmptyList(array $list): void
{
	[$a, $b] = $list;
	assertType('string', $a);
	assertType('string|null', $b);
}

function listFromConstantArray(): void
{
	$arr = [1, 'foo', true];
	[$a, $b, $c] = $arr;
	assertType('1', $a);
	assertType("'foo'", $b);
	assertType('true', $c);
}

/**
 * @param array{0: string, 1?: string} $arr
 */
function listFromOptionalKeys(array $arr): void
{
	[$a, $b] = $arr;
	assertType('string', $a);
	assertType('string|null', $b);
}

function nullCoalesceAfterList(string $input): void
{
	[$a, $b] = explode('-', $input);
	$x = $a ?? 'default';
	$y = $b ?? 'default';
	assertType('string', $x);
	assertType('string', $y);
}
