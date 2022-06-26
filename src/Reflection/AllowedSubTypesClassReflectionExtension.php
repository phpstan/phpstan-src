<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Type\Type;

/** @api */
interface AllowedSubTypesClassReflectionExtension
{

	public function supports(ClassReflection $classReflection): bool;

	/**
	 * @return array<Type>
	 */
	public function getAllowedSubTypes(ClassReflection $classReflection): array;

}
