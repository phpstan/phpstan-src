<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use CircularReferences\FirstClassInCycle;
use CircularReferences\FirstTraitInCycle;
use CircularReferences\TraitUsingSelf;
use CircularReferences\UsesTraitCycle;
use CircularReferences\UsesTraitUsingSelf;
use PHPStan\Testing\PHPStanTestCase;
use function array_keys;

/**
 * The classes under test reference each other in a cycle, which is a fatal error in PHP.
 * Walking such a hierarchy used to never terminate. A class-like on a cycle has no supertypes
 * of its kind, so a trait on a trait cycle uses no trait and a class on a parent class cycle
 * has no parent.
 */
class CircularReferenceClassReflectionTest extends PHPStanTestCase
{

	public function testGetTraitsOfClassUsingSelfUsingTrait(): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$this->assertSame(
			[TraitUsingSelf::class],
			array_keys($reflectionProvider->getClass(UsesTraitUsingSelf::class)->getTraits(true)),
		);
	}

	public function testGetTraitsOfClassUsingTraitCycle(): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$this->assertSame(
			[FirstTraitInCycle::class],
			array_keys($reflectionProvider->getClass(UsesTraitCycle::class)->getTraits(true)),
		);
	}

	public function testGetAncestorsOfClassUsingSelfUsingTrait(): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$this->assertSame(
			[UsesTraitUsingSelf::class, TraitUsingSelf::class],
			array_keys($reflectionProvider->getClass(UsesTraitUsingSelf::class)->getAncestors()),
		);
	}

	public function testGetAncestorsOfClassUsingTraitCycle(): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$this->assertSame(
			[UsesTraitCycle::class, FirstTraitInCycle::class],
			array_keys($reflectionProvider->getClass(UsesTraitCycle::class)->getAncestors()),
		);
	}

	public function testGetAncestorsOfTraitInCycle(): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$this->assertSame(
			[FirstTraitInCycle::class],
			array_keys($reflectionProvider->getClass(FirstTraitInCycle::class)->getAncestors()),
		);
	}

	public function testGetTraitsOfClassInParentClassCycle(): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$classReflection = $reflectionProvider->getClass(FirstClassInCycle::class);
		$this->assertNull($classReflection->getParentClass());
		$this->assertSame([], $classReflection->getTraits(true));
	}

	public function testClassHierarchyDistancesOfClassInParentClassCycle(): void
	{
		$reflectionProvider = self::createReflectionProvider();

		$this->assertSame(
			[FirstClassInCycle::class => 0],
			$reflectionProvider->getClass(FirstClassInCycle::class)->getClassHierarchyDistances(),
		);
	}

}
