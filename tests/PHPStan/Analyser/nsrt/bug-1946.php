<?php

declare(strict_types = 1);

namespace Bug1946;

use function PHPStan\Testing\assertType;

function foreachAllBranchesBreakWithAssignment(): void
{
	$tag = null;
	foreach (["a", "b", "c"] as $tag) {
		if ($tag === "a") {
			$tag = null;
			break;
		} else {
			$tag = null;
			break;
		}
	}

	assertType('null', $tag);
}

function foreachIfElseBreakDifferentTypes(): void
{
	$tag = null;
	foreach (["a", "b", "c"] as $tag) {
		if ($tag === "a") {
			$tag = 1;
			break;
		} else {
			$tag = 2;
			break;
		}
	}

	assertType('1|2', $tag);
}

function foreachAllBranchesReturn(): void
{
	$tag = null;
	foreach (["a", "b", "c"] as $tag) {
		if ($tag === "a") {
			$tag = null;
			return;
		} else {
			$tag = null;
			return;
		}
	}

	assertType('null', $tag);
}

function foreachOnlyIfBreaksNoElse(): void
{
	$tag = null;
	foreach (["a", "b", "c"] as $tag) {
		if ($tag === "a") {
			$tag = null;
			break;
		}
	}

	assertType("'c'|null", $tag);
}

/**
 * @param string[] $arr
 */
function foreachNonConstantArrayAllBreak(array $arr): void
{
	$tag = null;
	foreach ($arr as $tag) {
		if ($tag === "a") {
			$tag = null;
			break;
		} else {
			$tag = null;
			break;
		}
	}

	assertType('null', $tag);
}

function foreachElseIfAllBreak(): void
{
	$tag = null;
	foreach (["a", "b", "c"] as $tag) {
		if ($tag === "a") {
			$tag = 1;
			break;
		} elseif ($tag === "b") {
			$tag = 2;
			break;
		} else {
			$tag = 3;
			break;
		}
	}

	assertType('1|2|3', $tag);
}

function foreachBreakWithContinue(): void
{
	$tag = null;
	foreach (["a", "b", "c"] as $tag) {
		if ($tag === "a") {
			$tag = null;
			continue;
		} else {
			$tag = null;
			break;
		}
	}

	assertType('null', $tag);
}

function whileAllBreakMayNotIterate(): void
{
	$x = 'hello';
	while (rand(0, 1)) {
		if (rand(0, 1)) {
			$x = 1;
			break;
		} else {
			$x = 2;
			break;
		}
	}

	assertType("1|2|'hello'", $x);
}

function whileTrueAllBreak(): void
{
	$x = 'hello';
	while (true) {
		if (rand(0, 1)) {
			$x = 1;
			break;
		} else {
			$x = 2;
			break;
		}
	}

	assertType('1|2', $x);
}

function doWhileAllBreak(): void
{
	$x = 'hello';
	do {
		if (rand(0, 1)) {
			$x = 1;
			break;
		} else {
			$x = 2;
			break;
		}
	} while (rand(0, 1));

	assertType('1|2', $x);
}

function forAllBreakAlwaysIterates(): void
{
	$x = 'hello';
	for ($i = 0; $i < 10; $i++) {
		if (rand(0, 1)) {
			$x = 1;
			break;
		} else {
			$x = 2;
			break;
		}
	}

	assertType('1|2', $x);
}
