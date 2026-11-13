<?php declare(strict_types = 1);

namespace Bug15726;

use function PHPStan\Testing\assertType;

/** @return array{mixed}|false */
function fetchRow()
{
	return rand(0, 1) === 1 ? [rand(0, 1)] : false;
}

function foo(): void
{
	$prev = -1;
	$printed = false;
	while ($row = fetchRow()) {
		if ($row[0]) {
			$printed = true;
		}
		$prev = rand(0, 3);
	}
	assertType('false', $row);
	if ($prev !== -1) {
		assertType('bool', $printed); // the last row's [0] can be falsy
	}
	if ($prev != -1) {
		assertType('bool', $printed);
	}
}

function postDecrement(): void
{
	$i = 10;
	while ($i-- > 0) {
		assertType('int<0, 9>', $i);
	}
	assertType('-1', $i);
}

// https://github.com/phpstan/phpstan/issues/15722 Problem B

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
