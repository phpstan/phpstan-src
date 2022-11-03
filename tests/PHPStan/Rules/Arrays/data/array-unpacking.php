<?php // lint >= 7.4

namespace ArrayUnpacking;

$foo = ['foo' => 'bar', 1, 2, 3];

$bar = [...$foo];

/** @param array<int, string> $bar */
function intKeyedArray(array $bar)
{
	$baz = [...$bar];
}

/** @param array<string, string> $bar */
function stringKeyedArray(array $bar)
{
	$baz = [...$bar];
}

/** @param array<array-key, string> $bar */
function benevolentUnionKeyedArray(array $bar)
{
	$baz = [...$bar];
}

function mixedKeyedArray(array $bar)
{
	$baz = [...$bar];
}

/**
 * @param array<mixed, string> $foo
 * @param array<int, string> $bar
 */
function multipleUnpacking(array $foo, array $bar)
{
	$baz = [
		...$bar,
		...$foo,
	];
}

/**
 * @param array<mixed, string> $foo
 * @param array<string, string> $bar
 */
function foo(array $foo, array $bar)
{
	$baz = [
		$bar,
		...$foo
	];
}

/**
 * @param array{foo: string, bar:int} $foo
 * @param array{1, 2, 3, 4} $bar
 */
function unpackingArrayShapes(array $foo, array $bar)
{
	$baz = [
		...$foo,
		...$bar,
	];
}

/** @param array<int|string, string> $bar */
function unionKeyedArray(array $bar)
{
	$baz = [...$bar];
}
