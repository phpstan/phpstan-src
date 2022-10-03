<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/** @api */
interface ClassMemberAccessAnswerer
{

	/**
	 * @phpstan-assert-if-true !null $this->getClassReflection()
	 */
	public function isInClass(): bool;

	public function getClassReflection(): ?ClassReflection;

	public function canAccessProperty(PropertyReflection $propertyReflection): bool;

	public function canCallMethod(MethodReflection $methodReflection): bool;

	public function canAccessConstant(ConstantReflection $constantReflection): bool;

}
