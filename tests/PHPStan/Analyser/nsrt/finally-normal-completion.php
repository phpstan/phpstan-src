<?php declare(strict_types = 1);

namespace FinallyNormalCompletion;

use function PHPStan\Testing\assertType;

function maybeThrow(): void
{
	if (rand(0, 1) === 1) {
		throw new \RuntimeException();
	}
}

// the statement after the try-catch is reached only through the normal
// completion: what the finally block computes there comes from that state,
// not from the join of every way into the finally block

function earlyReturn(bool $c): void
{
	$y = null;
	try {
		if ($c) {
			$y = 'r';
			return;
		}
		$y = 5;
	} finally {
		assertType('5|\'r\'', $y);
		$w = $y;
	}
	assertType('5', $y);
	assertType('5', $w);
}

function incrementedInFinally(): void
{
	$n = 0;
	try {
		$n = 1;
		maybeThrow();
		$n = 2;
	} catch (\RuntimeException $e) {
		$n = 3;
		return;
	} finally {
		$n++;
	}
	assertType('3', $n);
}

function definedOnlyByTheTry(): void
{
	try {
		maybeThrow();
		$a = 1;
	} finally {
		$b = $a ?? null;
	}
	assertType('1', $a);
	assertType('1', $b);
}

function unchangedFinallyEntry(int $i): void
{
	try {
		$i = 1;
	} finally {
		$j = $i;
	}
	assertType('1', $j);
}
