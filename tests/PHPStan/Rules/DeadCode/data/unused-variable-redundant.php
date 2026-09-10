<?php declare(strict_types = 1);

namespace UnusedVariableRedundant;

/** @phpstan-impure */
function cond(): bool
{
	return (bool) rand(0, 1);
}

/** @param mixed $v */
function sink($v): void
{
}

/** @return true */
function alwaysTrue(): bool
{
	return true;
}

function redundantTrueInBranch(): void
{
	$x = true;
	if (cond()) {
		$x = true;
	}
	sink($x);
}

function differentValueInBranch(): void
{
	$x = true;
	if (cond()) {
		$x = false;
	}
	sink($x);
}

function redundantSequential(): void
{
	$x = 1;
	$x = 1;
	sink($x);
}

function redundantNull(): void
{
	$x = null;
	if (cond()) {
		$x = null;
	}
	sink($x);
}

function redundantString(): void
{
	$s = 'a';
	if (cond()) {
		$s = 'a';
	}
	sink($s);
}

function redundantConstantArray(): void
{
	$a = ['k' => 1];
	if (cond()) {
		$a = ['k' => 1];
	}
	sink($a);
}

function wideBoolIsNotRedundant(): void
{
	$x = cond();
	if (cond()) {
		$x = true;
	}
	sink($x);
}

function nativeTypeMustAgree(): void
{
	$x = true;
	if (cond()) {
		$x = alwaysTrue();
	}
	sink($x);
}

function maybeDefinedIsNotRedundant(): void
{
	if (cond()) {
		$x = true;
	}
	$x = true;
	sink($x);
}

function firstAssignmentIsNotRedundant(): void
{
	$x = true;
	sink($x);
}

function readModifyWriteIsNotConsidered(): void
{
	$i = 1;
	$i += 0;
	sink($i);
}

function redundantInEveryLoopIteration(): void
{
	$x = 1;
	while (cond()) {
		$x = 1;
	}
	sink($x);
}

function redundantOnFirstPassOnly(): void
{
	$x = 1;
	while (cond()) {
		sink($x);
		$x = $x + 1;
		if (cond()) {
			$x = 1;
		}
	}
	sink($x);
}
