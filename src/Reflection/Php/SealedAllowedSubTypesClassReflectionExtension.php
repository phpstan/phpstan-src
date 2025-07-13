<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\AllowedSubTypesClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\UnionType;
use function count;

#[AutowiredService]
final class SealedAllowedSubTypesClassReflectionExtension implements AllowedSubTypesClassReflectionExtension
{

	public function supports(ClassReflection $classReflection): bool
	{
		return count($classReflection->getSealedTags()) > 0;
	}

	public function getAllowedSubTypes(ClassReflection $classReflection): array
	{
		$types = [];

		foreach ($classReflection->getSealedTags() as $sealedTag) {
			$type = $sealedTag->getType();
			if ($type instanceof UnionType) {
				$types = $type->getTypes();
			} else {
				$types = [$type];
			}
		}

		return $types;
	}

}
