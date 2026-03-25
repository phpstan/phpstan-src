<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14369DeadCode;

use Exception;

function test(string|null $test): void
{
	$test ??= throw new Exception();

	echo $test;
}

function testMaybeNull(): void
{
	if (rand(0, 1)) {
		$test = null;
	} else {
		$test = 'hello';
	}
	$test ??= throw new Exception();

	echo $test;
}

function testAlwaysNull(): void
{
	$test = null;
	$test ??= throw new Exception();

	echo $test;
}

function testAlwaysTerminatingLhs(): void
{
	alwaysThrows()->prop ??= throw new Exception();

	echo 'unreachable';
}

/** @return never */
function alwaysThrows(): never
{
	throw new Exception();
}
