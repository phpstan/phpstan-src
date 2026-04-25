<?php

namespace Bug9519;

use function PHPStan\Testing\assertType;

class ClassA {}
class ClassB {}

function instanceofVariants(mixed $obj): void
{
	$isA = $obj instanceof ClassA;
	$isB = $obj instanceof ClassB;

	if ($isA || $isB) {
		assertType('Bug9519\\ClassA|Bug9519\\ClassB', $obj);
	}

	// Sanity check: the equivalent inline form has always worked, so the
	// stored-boolean form should produce the same narrowing.
	if (($obj instanceof ClassA) || ($obj instanceof ClassB)) {
		assertType('Bug9519\\ClassA|Bug9519\\ClassB', $obj);
	}
}

/**
 * Three-way OR over stored booleans — every arm narrows the same target.
 */
class ClassC {}

function threeWayInstanceof(mixed $obj): void
{
	$isA = $obj instanceof ClassA;
	$isB = $obj instanceof ClassB;
	$isC = $obj instanceof ClassC;

	if ($isA || $isB || $isC) {
		assertType('Bug9519\\ClassA|Bug9519\\ClassB|Bug9519\\ClassC', $obj);
	}
}

/**
 * Different narrowing kinds across the OR's arms — `null !==` on the left,
 * `instanceof` on the right.
 */
function mixedNarrowingKinds(?ClassA $a, mixed $b): void
{
	$aNotNull = $a !== null;
	$bIsB = $b instanceof ClassB;

	if ($aNotNull || $bIsB) {
		// Inside the truthy branch we don't know which arm fired, so each
		// target keeps the union of (narrowed-when-its-arm-fired)
		// and (original-when-the-other-arm-fired).
		assertType(
			'Bug9519\\ClassA|null',
			$a,
		);
		assertType(
			'mixed',
			$b,
		);
	}
}
