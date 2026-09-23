<?php declare(strict_types = 1);

namespace UnusedVariableInferredBranches;

/** @param mixed $v */
function sink($v): void
{
}

/** @param non-empty-list<int> $items */
function foreachOverNonEmptyType(array $items): void
{
	$last = null;
	foreach ($items as $item) {
		$last = $item;
	}
	sink($last);
}

/** @param array{} $items */
function foreachOverEmptyType(array $items): void
{
	$x = 1;
	foreach ($items as $item) {
		sink($x);
		sink($item);
	}
	$x = 2;
	sink($x);
}

function readInBranchInferredDead(int $i): void
{
	$x = 1;
	if (is_string($i)) {
		sink($x);
	}
	$x = 2;
	sink($x);
}

function overwrittenInBranchInferredAlwaysTaken(int $i): void
{
	$x = 1;
	if (is_int($i)) {
		$x = 2;
	}
	sink($x);
}

function switchInferredExhaustive(bool $b): void
{
	$x = 1;
	switch ($b) {
		case true:
			$x = 2;
			break;
		case false:
			$x = 3;
			break;
	}
	sink($x);
}

/** @param true $again */
function whileInferredAlwaysTrue(bool $again): void
{
	$x = 1;
	while ($again) {
		$x = 2;
		if (rand(0, 1) === 1) {
			break;
		}
	}
	sink($x);
}

/** @param false $again */
function doWhileInferredNeverRepeating(bool $again): void
{
	$x = 1;
	do {
		sink($x);
		$x = 2;
	} while ($again);
}

function literalConditionsDoNotDecideEither(): void
{
	$x = 1;
	if (true) {
		$x = 2;
	}
	sink($x);

	$y = 1;
	while (true) {
		$y = 2;
		if (rand(0, 1) === 1) {
			break;
		}
	}
	sink($y);

	$z = 1;
	foreach ([1, 2] as $item) {
		$z = $item;
	}
	sink($z);

	$w = 1;
	do {
		sink($w);
		$w = 2;
	} while (false);
}
