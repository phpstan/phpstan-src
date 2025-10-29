<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_unique;
use function array_values;
use function in_array;

#[AutowiredService]
final class IsAFunctionTypeSpecifyingHelper
{

	public function determineType(
		Type $objectOrClassType,
		Type $classType,
		bool $allowString,
		bool $allowSameClass,
	): ?Type
	{
		$objectOrClassTypeClassNames = $objectOrClassType->getObjectClassNames();
		if ($allowString) {
			foreach ($objectOrClassType->getConstantStrings() as $constantString) {
				$objectOrClassTypeClassNames[] = $constantString->getValue();
			}
			$objectOrClassTypeClassNames = array_values(array_unique($objectOrClassTypeClassNames));
		}

		$isUncertain = $classType->getConstantStrings() === [];

		$resultType = TypeTraverser::map(
			$classType,
			static function (Type $type, callable $traverse) use ($objectOrClassType, $objectOrClassTypeClassNames, $allowString, $allowSameClass, &$isUncertain): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}
				if ($type instanceof ConstantStringType) {
					if (!$allowSameClass) {
						if ($objectOrClassTypeClassNames === [$type->getValue()]) {
							$isSameClass = true;
							foreach ($objectOrClassType->getObjectClassReflections() as $classReflection) {
								if (!$classReflection->isFinal()) {
									$isSameClass = false;
									break;
								}
							}

							if ($isSameClass) {
								return new NeverType();
							}
						}

						if (
							// For object, as soon as the exact same type is provided
							// in the list we cannot be sure of the result
							in_array($type->getValue(), $objectOrClassTypeClassNames, true)
							// This also occurs for generic class string
							|| ($allowString && $objectOrClassTypeClassNames === [] && $objectOrClassType->isSuperTypeOf($type)->yes())
						) {
							$isUncertain = true;
						}
					}
					if ($allowString) {
						return new UnionType([
							new ObjectType($type->getValue()),
							new GenericClassStringType(new ObjectType($type->getValue())),
						]);
					}

					return new ObjectType($type->getValue());
				}
				if ($type instanceof GenericClassStringType) {
					if ($allowString) {
						return TypeCombinator::union(
							$type->getGenericType(),
							$type,
						);
					}

					return $type->getGenericType();
				}
				if ($allowString) {
					return new UnionType([
						new ObjectWithoutClassType(),
						new ClassStringType(),
					]);
				}

				return new ObjectWithoutClassType();
			},
		);

		// prevent false-positives
		if ($isUncertain && $resultType->isSuperTypeOf($objectOrClassType)->yes()) {
			return null;
		}

		return $resultType;
	}

}
