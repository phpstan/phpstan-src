<?php // lint >= 8.0

namespace DisjunctionHolderProjection;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class ClassA {}
class ClassB {}

class Foo
{

	public function maybeDefinedTargetStaysMaybe(bool $c, mixed $m): void
	{
		if ($c) {
			$obj = $m;
		}

		$isA = $obj instanceof ClassA;
		$isB = $obj instanceof ClassB;

		assertVariableCertainty(TrinaryLogic::createMaybe(), $obj);

		if ($isA || $isB) {
			// The projection of the stored-boolean holders must not fire for
			// $obj: it is only Maybe-defined here, and a projected sure type
			// would wrongly upgrade the certainty to Yes.
			assertVariableCertainty(TrinaryLogic::createMaybe(), $obj);
			assertType('mixed', $obj);
		}
	}

	public function reassignedTargetKeepsItsNewType(bool $c, mixed $m, mixed $m2): void
	{
		if ($c) {
			$obj = $m;
		}

		$isA = $obj instanceof ClassA;
		$isB = $obj instanceof ClassB;
		$cond = $isA || $isB;

		$obj = $m2;

		if ($cond) {
			// The stored-boolean branch reads were captured while $obj was
			// Maybe-defined; projecting them onto the reassigned $obj would
			// resurrect the stale compose-time narrowing.
			assertVariableCertainty(TrinaryLogic::createYes(), $obj);
			assertType('mixed', $obj);
		}
	}

	public function definedTargetIsProjected(mixed $obj): void
	{
		$isA = $obj instanceof ClassA;
		$isB = $obj instanceof ClassB;

		if ($isA || $isB) {
			assertType('DisjunctionHolderProjection\ClassA|DisjunctionHolderProjection\ClassB', $obj);
		}
	}

}
