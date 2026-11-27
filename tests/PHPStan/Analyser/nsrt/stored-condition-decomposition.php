<?php declare(strict_types = 1);

namespace StoredConditionDecomposition;

use function PHPStan\Testing\assertType;

function inline(?string $x, ?string $y): void
{
	if (($x !== null || $y !== null) && $x === null) {
		assertType('string', $y);
	}
}

function stored(?string $x, ?string $y): void
{
	$either = $x !== null || $y !== null;
	if ($either && $x === null) {
		assertType('string', $y);
	}
}

function storedNested(?string $x, ?string $y): void
{
	$either = $x !== null || $y !== null;
	if ($either) {
		if ($x === null) {
			assertType('string', $y);
		}
	}
}

function storedNegatedConjunction(?string $x, ?int $y): void
{
	$both = $x !== null && $y !== null;
	if ($both) {
		assertType('string', $x);
		assertType('int', $y);
	}
	if (!$both && $x !== null) {
		assertType('null', $y);
	}
}

function storedReassigned(?string $x, ?string $y): void
{
	$either = $x !== null || $y !== null;
	$x = null;
	if ($either && $x === null) {
		assertType('string|null', $y);
	}
}

function storedConjunctionOfNegations(bool $isMultiple, bool $isRequired): void
{
	$isToggleButton = (! $isMultiple) && (! $isRequired);
	if (! $isToggleButton) {
		if (! $isMultiple) {
			assertType('true', $isRequired);
		}
	}
}
