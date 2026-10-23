<?php

namespace ClosureUseConditionalGuardReassigned;

use function PHPStan\Testing\assertType;

function reassignedGuard(bool $c): void
{
	if ($c) {
		$a = 1;
		$b = 'x';
	} else {
		$a = 2;
		$b = 'y';
	}

	$f = function () use ($a, $b): void {
		if ($a === 1) {
			assertType("'x'", $b);
		}

		$a = rand(1, 2);
		if ($a === 1) {
			assertType("'x'|'y'", $b);
		}
	};
}

function guardOffsetWritten(bool $c): void
{
	if ($c) {
		$item = ['nested' => null, 'k' => 1];
		$walk = 'x';
	} else {
		$item = ['nested' => null, 'k' => 2];
		$walk = 'y';
	}

	$f = function () use ($item, $walk): void {
		$item['nested'] = 1;
		if ($item['k'] === 1) {
			assertType("'x'|'y'", $walk);
		}
		if ($item === ['nested' => 1, 'k' => 1]) {
			assertType("'x'|'y'", $walk);
		}
	};
}
