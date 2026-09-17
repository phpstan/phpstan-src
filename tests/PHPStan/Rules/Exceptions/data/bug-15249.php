<?php declare(strict_types = 1);

namespace Bug15249;

use Generator;
use RuntimeException;

/** @return Generator<string, int, mixed, void> */
function withKey(): Generator
{
	try {
		yield 'a' => 1;
	} catch (RuntimeException $e) {
		echo $e->getMessage();
	}
}

/** @return Generator<int, int, mixed, void> */
function withoutKey(): Generator
{
	try {
		yield 1;
	} catch (RuntimeException $e) {
		echo $e->getMessage();
	}
}

/** @return Generator<string, int, mixed, void> */
function withVariableKey(string $k): Generator
{
	try {
		yield $k => 1;
	} catch (RuntimeException $e) {
		echo $e->getMessage();
	}
}

/** @return Generator<string, int, mixed, void> */
function withKeyAssigned(): Generator
{
	try {
		$x = yield 'a' => 1;
	} catch (RuntimeException $e) {
		echo $e->getMessage();
	}
}

/** @return Generator<string, int, mixed, void> */
function withKeyNested(): Generator
{
	try {
		$x = [yield 'a' => 1];
	} catch (RuntimeException $e) {
		echo $e->getMessage();
	}
}

/** @return Generator<string, int, mixed, void> */
function dynamicMethodNameYield(\stdClass $o): Generator
{
	try {
		$o->{yield 'a' => 1}();
	} catch (RuntimeException $e) {
		echo $e->getMessage();
	}
}
