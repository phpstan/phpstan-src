<?php declare(strict_types = 1);

namespace Bug7718;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

/**
 * @param array<string, int> $elements
 */
function isset_twice(array $elements): void
{
	if (isset($elements['a'])) {
		$a = 1;
	}

	if (isset($elements['a'])) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}
}

function strict_compare_twice(string $s): void
{
	if ($s === 'banana') {
		$bananas = 1;
	}

	if ($s === 'banana') {
		assertVariableCertainty(TrinaryLogic::createYes(), $bananas);
	}
}

function int_compare_twice(int $x): void
{
	if ($x === 1) {
		$a = 1;
	}

	if ($x === 1) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}
}

/**
 * @param array<string, int> $elements
 */
function array_key_exists_twice(array $elements): void
{
	if (array_key_exists('a', $elements)) {
		$a = 1;
	}

	if (array_key_exists('a', $elements)) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}
}

function instanceof_twice(mixed $x): void
{
	if ($x instanceof \Throwable) {
		$a = 1;
	}

	if ($x instanceof \Throwable) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}
}

function not_null_twice(?int $x): void
{
	if ($x !== null) {
		$a = 1;
	}

	if ($x !== null) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}
}

// A different condition must NOT prove the variable defined.
function different_condition(string $s): void
{
	if ($s === 'a') {
		$a = 1;
	}

	if ($s === 'b') {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	}
}

/**
 * @param array<string, int> $elements
 */
function isset_then_else(array $elements): void
{
	if (isset($elements['a'])) {
		$a = 1;
	} else {
		$a = 2;
	}

	if (isset($elements['a'])) {
		assertVariableCertainty(TrinaryLogic::createYes(), $a);
	}
}

// Reusing only part of an `&&` condition must NOT prove the variable defined.
function partial_condition_reuse(int $x, int $y): void
{
	if ($x === 1 && $y === 2) {
		$a = 1;
	}

	if ($x === 1) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
	}
}

// A non-deterministic condition must NOT prove the variable defined when reused.
function impure_condition(int $x): void
{
	if (rand(0, 1) === 1 && $x === 1) {
		$v = 1;
	}

	if ($x === 1) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $v);
	}
}
