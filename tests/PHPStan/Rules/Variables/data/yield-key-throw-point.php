<?php declare(strict_types = 1);

namespace YieldKeyThrowPointVariables;

use Generator;
use RuntimeException;

/** @return Generator<string, int, mixed, void> */
function withKey(): Generator
{
	try {
		$a = 1;
		yield 'k' => 2;
		$b = 3;
	} catch (RuntimeException $e) {
		echo $a;
		echo $b;
	}
}

/** @return Generator<int, int, mixed, void> */
function withoutKey(): Generator
{
	try {
		$a = 1;
		yield 2;
		$b = 3;
	} catch (RuntimeException $e) {
		echo $a;
		echo $b;
	}
}
