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

function noSideEffectInCondition(): void
{
	$x = 5;
	while ($x > 0) {
		$x = $x - 1;
	}

	assertType('0', $x);
}
