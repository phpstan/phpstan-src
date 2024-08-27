<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Enum\EnumCaseObjectType;
use function array_keys;

final class EnumAllowedSubTypesClassReflectionExtension implements AllowedSubTypesClassReflectionExtension
{

	public function supports(ClassReflection $classReflection): bool
	{
		return $classReflection->isEnum();
	}

	public function getAllowedSubTypes(ClassReflection $classReflection): array
	{
		$cases = [];
		foreach (array_keys($classReflection->getEnumCases()) as $name) {
			$cases[] = new EnumCaseObjectType($classReflection->getName(), $name);
		}

		return $cases;
	}

}
