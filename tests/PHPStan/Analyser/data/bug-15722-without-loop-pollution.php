<?php declare(strict_types = 1);

namespace Bug15722WithoutLoopPollution;

use function PHPStan\Testing\assertType;

interface Source
{
	public function stop(): bool;
	public function next(): int;
}

function loop(Source $s): void
{
	$rows = 0;
	while (!($stop = $s->stop()) && ($rows = $s->next())) {
	}

	if ($rows !== 0) {
		assertType('true', $stop);
	}

	if ($rows === 0) {
		assertType('bool', $stop);
	} else {
		assertType('true', $stop);
	}
}

function noCorrelation(): void
{
	$n = 0;
	$flag = false;
	while (rand(0, 1)) {
		$n = 1;
		$flag = (bool) rand(0, 1);
	}
	if ($n === 1) {
		assertType('bool', $flag);
	}
}

function guardOverlaps(): void
{
	$n = rand(0, 2);
	$flag = false;
	while (rand(0, 1)) {
		$n = 1;
		$flag = true;
	}
	if ($n === 1) {
		// the loop may not have run, with $n = 1 and $flag still false
		assertType('bool', $flag);
	}
}
