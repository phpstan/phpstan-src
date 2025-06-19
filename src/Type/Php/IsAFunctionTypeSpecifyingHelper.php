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

#[AutowiredService]
final class IsAFunctionTypeSpecifyingHelper
{

	public function determineType(
		Type $objectOrClassType,
		Type $classType,
		bool $allowString,
		bool $allowSameClass,
	): Type
	{
		$objectOrClassTypeClassNames = $objectOrClassType->getObjectClassNames();
		if ($allowString) {
			foreach ($objectOrClassType->getConstantStrings() as $constantString) {
				$objectOrClassTypeClassNames[] = $constantString->getValue();
			}
			$objectOrClassTypeClassNames = array_values(array_unique($objectOrClassTypeClassNames));
		}

		return TypeTraverser::map(
			$classType,
			static function (Type $type, callable $traverse) use ($objectOrClassTypeClassNames, $allowString, $allowSameClass): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}
				if ($type instanceof ConstantStringType) {
					if (!$allowSameClass && $objectOrClassTypeClassNames === [$type->getValue()]) {
						return new NeverType();
					}
					if ($allowString) {
						return TypeCombinator::union(
							new ObjectType($type->getValue()),
							new GenericClassStringType(new ObjectType($type->getValue())),
						);
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
					return TypeCombinator::union(
						new ObjectWithoutClassType(),
						new ClassStringType(),
					);
				}

				return new ObjectWithoutClassType();
			},
		);
	}

}
