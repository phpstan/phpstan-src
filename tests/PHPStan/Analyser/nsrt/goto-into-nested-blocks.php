<?php declare(strict_types = 1);

namespace GotoIntoNestedBlocks;

use function PHPStan\Testing\assertType;

// a goto may jump into an if, else or try block: the label nested there is
// entered in the goto's state, without the conditions of the blocks around it

function forwardIntoIf(bool $a, bool $b): void
{
	$x = 0;
	if ($a) {
		goto inside;
	}
	if ($b) {
		$x = 1.5;
		inside:
		assertType('0|1.5', $x);
	}
	assertType('0|1.5', $x);
}

function backwardIntoIf(bool $a, bool $b): void
{
	$x = 0;
	if ($b) {
		inside:
		assertType('0|1.5', $x);
	}
	if ($a) {
		$x = 1.5;
		goto inside;
	}
}

function fromElseIntoIf(bool $c): void
{
	$x = 0;
	if ($c) {
		inside:
		assertType('0|\'e\'', $x);
		return;
	} else {
		$x = 'e';
		goto inside;
	}
}

function notNarrowedByTheCondition(int $x, bool $again): void
{
	if ($x === 0) {
		inside:
		assertType('0|5', $x);
	}
	if ($again) {
		$x = 5;
		goto inside;
	}
}

function counter(bool $c): void
{
	$i = 0;
	if ($c) {
		start:
		assertType('int<0, max>', $i);
	}
	$i++;
	if ($i < 5) {
		goto start;
	}
}

function reachedOnlyByTheGoto(bool $b): void
{
	$x = 0;
	goto inside;
	if ($b) {
		$x = 1.5;
		inside:
		assertType('0|1.5', $x);
	}
	assertType('0|1.5', $x);
}
