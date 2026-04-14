<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14466;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use function PHPStan\Testing\assertType;

interface I
{

}

class Bug
{
	/**
	 * @param ReflectionClass<*> $object
	 */
	protected function c(ReflectionClass $object): void
	{
		$requirements = $object->getAttributes(I::class, ReflectionAttribute::IS_INSTANCEOF);

		assertType('list<ReflectionAttribute<Bug14466\I>>', $requirements);
	}

	/**
	 * @param ReflectionMethod $object
	 */
	protected function m(ReflectionMethod $object): void
	{
		$requirements = $object->getAttributes(I::class, ReflectionAttribute::IS_INSTANCEOF);

		assertType('list<ReflectionAttribute<Bug14466\I>>', $requirements);
	}

	/**
	 * @param ReflectionClass<*>|ReflectionMethod $object
	 */
	protected function classOrMethod(ReflectionClass|ReflectionMethod $object): void
	{
		$requirements = $object->getAttributes(I::class, ReflectionAttribute::IS_INSTANCEOF);

		assertType('list<ReflectionAttribute<Bug14466\I>>', $requirements);
	}

	/**
	 * @param ReflectionClass<*>|\ReflectionProperty $object
	 */
	protected function classOrProperty(ReflectionClass|\ReflectionProperty $object): void
	{
		$requirements = $object->getAttributes(I::class, ReflectionAttribute::IS_INSTANCEOF);

		assertType('list<ReflectionAttribute<Bug14466\I>>', $requirements);
	}

	/**
	 * @param ReflectionMethod|\ReflectionProperty $object
	 */
	protected function methodOrProperty(ReflectionMethod|\ReflectionProperty $object): void
	{
		$requirements = $object->getAttributes(I::class, ReflectionAttribute::IS_INSTANCEOF);

		assertType('list<ReflectionAttribute<Bug14466\I>>', $requirements);
	}
}
