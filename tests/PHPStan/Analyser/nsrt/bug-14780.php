<?php declare(strict_types = 1);

namespace Bug14780;

use function PHPStan\Testing\assertType;

function withBothGuards(bool $forAll, ?int $expiration, ?int $auto): void
{
	if ($forAll && $expiration !== null) {
		throw new \LogicException('A');
	}
	if (!$forAll && $expiration === null && $auto !== null) {
		throw new \LogicException('B');
	}

	// Reachable with $auto !== null:  $forAll=true, $expiration=null, $auto=5
	//   A: true && false  -> no throw
	//   B: false && ...    -> no throw
	if ($forAll) {
		assertType('int|null', $auto);
	}
}

function singleGuard(bool $forAll, ?int $expiration, ?int $auto): void
{
	if (!$forAll && $expiration === null && $auto !== null) {
		throw new \LogicException('B');
	}

	// $expiration is unknown here, so $auto must keep both possibilities.
	if (!$forAll) {
		assertType('int|null', $auto);
	}
}

function fourConditions(bool $a, bool $b, ?int $expiration, ?int $auto): void
{
	if ($a && $expiration !== null) {
		throw new \LogicException('A');
	}
	if (!$a && $b && $expiration === null && $auto !== null) {
		throw new \LogicException('B');
	}

	// Entering `if ($a)` chains `$a => $expiration === null` (guard A) into the
	// guard B holder, which must keep all three of its conjuncts.
	if ($a) {
		assertType('int|null', $auto);
	}
}

function compoundHolderSide(?int $a, ?object $b, bool $mock): void
{
	// The guarded side `$a === null && $b === null` is itself a conjunction.
	// Its negation `$a !== null || $b !== null` is a disjunction and must not be
	// split into independent `$mock => $a !== null` / `$mock => $b !== null`
	// holders that would each over-narrow.
	if ($a === null && $b === null && !$mock) {
		throw new \LogicException();
	}

	if ($mock) {
		return;
	}

	// only (a !== null || b !== null) is known here
	assertType('int|null', $a);
	assertType('object|null', $b);
}
