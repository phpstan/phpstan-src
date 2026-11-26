<?php declare(strict_types = 1);

namespace ConditionalExpressionNativeTypes;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

function storedCondition(?int $x): void
{
	$ok = $x !== null;
	if ($ok) {
		assertType('int', $x);
		assertNativeType('int', $x);
	}
}

function branchMerge(?int $x, bool $b): void
{
	if ($b) {
		$y = $x ?? 1;
	} else {
		$y = null;
	}
	if ($b) {
		assertType('int', $y);
		assertNativeType('int', $y);
	}
}

/** @param int|null $x */
function phpDocTarget($x): void
{
	$ok = $x !== null;
	if ($ok) {
		assertType('int', $x);
		assertNativeType('mixed', $x);
	}
}

/** @param bool $b */
function phpDocGuard(?int $x, $b): void
{
	if ($b) {
		$y = $x ?? 1;
	} else {
		$y = null;
	}
	if ($b) {
		assertType('int', $y);
		assertNativeType('int|null', $y);
	}
}

/** @param int|null $x */
function nativeNarrowedLater($x): void
{
	$ok = $x !== null;
	if (is_int($x) || $x === null) {
		if ($ok) {
			assertType('int', $x);
			assertNativeType('int', $x);
		}
	}
}
