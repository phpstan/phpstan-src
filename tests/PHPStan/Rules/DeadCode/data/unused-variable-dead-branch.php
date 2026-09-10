<?php declare(strict_types = 1);

namespace UnusedVariableDeadBranch;

/** @param mixed $v */
function sink($v): void
{
}

function writeInAlwaysFalseBranch(): void
{
	$x = 1;
	if (false) {
		$x = 2;
	}
	sink($x);
}

function writeInAlwaysFalseGuard(string $s): void
{
	$result = 'a';
	if (is_int($s)) {
		$result = 'b';
	}
	sink($result);
}

function writeInDeadElse(): void
{
	$x = 1;
	if (true) {
		sink($x);
	} else {
		$y = 2;
		sink($y);
	}
}

function writeInDeadElseif(int $i): void
{
	$x = 1;
	if ($i > 0) {
		sink($x);
	} elseif (false) {
		$x = 3;
	}
	sink($x);
}

function writeInElseifAfterAlwaysTrue(int $i): void
{
	$x = 1;
	if (true) {
		sink($x);
	} elseif ($i > 0) {
		$x = 3;
	}
	sink($x);
}

function deadBranchInsideLoop(array $items): void
{
	$result = [];
	foreach ($items as $item) {
		if (false) {
			$result[] = 1;
		} else {
			$result[] = 2;
		}
	}
	sink($result);
	sink($item ?? null);
}

function liveBranchesStillReport(int $i): void
{
	$x = 1;
	if ($i > 0) {
		$x = 2;
		$x = 3;
	}
	sink($x);
}

/**
 * @param mixed $maybeint
 */
function absint($maybeint): int
{
	if (!is_numeric($maybeint)) {
		return 0;
	}
	$value = abs((int) $maybeint);
	if (is_float($value)) {
		$value = PHP_INT_MAX;
	}

	return $value;
}
