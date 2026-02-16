<?php declare(strict_types = 1);

namespace Bug13000;

use function PHPStan\Testing\assertType;

function basicConstantArrayForeach(): void
{
	$r = [];
	foreach (['a' => '1', 'b' => '2'] as $key => $val) {
		$r[$key] = $val;
	}
	assertType("array{a: '1', b: '2'}", $r);
}

function constantArrayForeachWithTransform(): void
{
	$r = [];
	foreach (['a' => 'hello', 'b' => 'world'] as $key => $val) {
		$r[$key] = strtoupper($val);
	}
	assertType("array{a: 'HELLO', b: 'WORLD'}", $r);
}

/**
 * @param array{a: string, b: string} $input
 */
function constantArrayForeachFromParam(array $input): void
{
	$r = [];
	foreach ($input as $key => $val) {
		$r[$key] = strtoupper($val);
	}
	assertType("array{a: uppercase-string, b: uppercase-string}", $r);
}

/**
 * @return array{a: string, b: string}
 */
function returnTypeIsCompatible(): array
{
	$r = [];
	foreach (['a' => '1', 'b' => '2'] as $key => $val) {
		$r[$key] = $val;
	}
	assertType("array{a: '1', b: '2'}", $r);
	return $r;
}

function integerKeys(): void
{
	$r = [];
	foreach ([10 => 'x', 20 => 'y'] as $key => $val) {
		$r[$key] = $val;
	}
	assertType("array{10: 'x', 20: 'y'}", $r);
}

/**
 * @param array{x: int, y: int, z: int} $coords
 */
function threeKeys(array $coords): void
{
	$r = [];
	foreach ($coords as $key => $val) {
		$r[$key] = $val * 2;
	}
	assertType("array{x: int, y: int, z: int}", $r);
}
