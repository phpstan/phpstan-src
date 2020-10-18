<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

class PropertyReflectionFinder
{

	/**
	 * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
	 * @param \PHPStan\Analyser\Scope $scope
	 * @return FoundPropertyReflection|null
	 */
	public function findPropertyReflectionFromNode($propertyFetch, Scope $scope): ?FoundPropertyReflection
	{
		if ($propertyFetch instanceof \PhpParser\Node\Expr\PropertyFetch) {
			if (!$propertyFetch->name instanceof \PhpParser\Node\Identifier) {
				return null;
			}
			$propertyHolderType = $scope->getType($propertyFetch->var);
			$fetchedOnThis = $propertyHolderType instanceof ThisType && $scope->isInClass();
			return $this->findPropertyReflection($propertyHolderType, $propertyFetch->name->name, $scope, $fetchedOnThis);
		}

		if (!$propertyFetch->name instanceof \PhpParser\Node\Identifier) {
			return null;
		}

		if ($propertyFetch->class instanceof \PhpParser\Node\Name) {
			$propertyHolderType = new ObjectType($scope->resolveName($propertyFetch->class));
		} else {
			$propertyHolderType = $scope->getType($propertyFetch->class);
		}

		$fetchedOnThis = $propertyHolderType instanceof ThisType && $scope->isInClass();

		return $this->findPropertyReflection($propertyHolderType, $propertyFetch->name->name, $scope, $fetchedOnThis);
	}

	private function findPropertyReflection(Type $propertyHolderType, string $propertyName, Scope $scope, bool $fetchedOnThis): ?FoundPropertyReflection
	{
		$transformedPropertyHolderType = TypeTraverser::map($propertyHolderType, static function (Type $type, callable $traverse) use ($scope, $fetchedOnThis): Type {
			if ($type instanceof StaticType) {
				if ($fetchedOnThis && $scope->isInClass()) {
					return $traverse($type->changeBaseClass($scope->getClassReflection()));
				}
				if ($scope->isInClass()) {
					return $traverse($type->changeBaseClass($scope->getClassReflection())->getStaticObjectType());
				}
			}

			return $traverse($type);
		});

		if (!$transformedPropertyHolderType->hasProperty($propertyName)->yes()) {
			return null;
		}

		$originalProperty = $transformedPropertyHolderType->getProperty($propertyName, $scope);
		$readableType = $this->transformPropertyType($originalProperty->getReadableType(), $transformedPropertyHolderType, $scope, $fetchedOnThis);
		$writableType = $this->transformPropertyType($originalProperty->getWritableType(), $transformedPropertyHolderType, $scope, $fetchedOnThis);

		return new FoundPropertyReflection(
			$originalProperty,
			$readableType,
			$writableType
		);
	}

	private function transformPropertyType(Type $propertyType, Type $transformedPropertyHolderType, Scope $scope, bool $fetchedOnThis): Type
	{
		return TypeTraverser::map($propertyType, static function (Type $propertyType, callable $traverse) use ($transformedPropertyHolderType, $scope, $fetchedOnThis): Type {
			if ($propertyType instanceof StaticType) {
				if ($fetchedOnThis && $scope->isInClass()) {
					return $traverse($propertyType->changeBaseClass($scope->getClassReflection()));
				}

				return $traverse($transformedPropertyHolderType);
			}

			return $traverse($propertyType);
		});
	}

}
