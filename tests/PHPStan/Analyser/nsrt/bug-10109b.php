<?php

namespace Bug10109b;

use function PHPStan\Testing\assertType;

function simple(): void
{
	$x = 5;
	while (--$x > 0) {
	}

	assertType('int<min, 0>', $x);
}

function withBody(): void
{
	$x = 5;
	while (--$x > 0) {
		echo $x;
	}

	assertType('int<min, 0>', $x);
}

function preIncrement(): void
{
	$x = 5;
	while (++$x < 10) {
	}

	assertType('int<10, max>', $x);
}

function assignInCondition(): void
{
	$x = 5;
	while (($x = $x - 1) > 0) {
	}

	assertType('int<min, 0>', $x);
}

function postDecrement(): void
{
	$x = 5;
	while ($x-- > 0) {
	}

	assertType('int<min, -1>', $x);
}

function forLoop(): void
{
	for ($x = 5; --$x > 0;) {
	}

	assertType('int<min, 0>', $x);
}

function shortCircuitedSideEffect(): void
{
	// The side effect is short-circuited, so the loop can also exit with $x unchanged. The
	// after-loop type must still include the values produced by the decrement (notably 0).
	$x = 5;
	while (mt_rand(0, 10) < 10 && --$x > 0) {
	}

	assertType('int<min, 5>', $x);
}

function shortCircuitedCounter(): void
{
	// A post-increment counter keeps its precise in-loop bound instead of being widened by the
	// loop-condition falsey scope.
	$i = 0;
	while (mt_rand(0, 10) < 10 && $i++ < 10) {
	}

	assertType('int<0, 10>', $i);
}

function noSideEffectInCondition(): void
{
	$x = 5;
	while ($x > 0) {
		$x = $x - 1;
	}

	assertType('0', $x);
}
