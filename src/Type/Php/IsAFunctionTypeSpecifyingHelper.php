<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

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
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;

final class IsAFunctionTypeSpecifyingHelper
{

	public function determineType(
		Type $objectOrClassType,
		Type $classType,
		bool $allowString,
		bool $allowSameClass,
	): Type
	{
		$objectOrClassTypeClassName = $this->determineClassNameFromObjectOrClassType($objectOrClassType, $allowString);

		return TypeTraverser::map(
			$classType,
			static function (Type $type, callable $traverse) use ($objectOrClassTypeClassName, $allowString, $allowSameClass): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}
				if ($type instanceof ConstantStringType) {
					if (!$allowSameClass && $type->getValue() === $objectOrClassTypeClassName) {
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

	private function determineClassNameFromObjectOrClassType(Type $type, bool $allowString): ?string
	{
		if ($type instanceof TypeWithClassName) {
			return $type->getClassName();
		}

		if ($allowString && $type instanceof ConstantStringType) {
			return $type->getValue();
		}

		return null;
	}

}
