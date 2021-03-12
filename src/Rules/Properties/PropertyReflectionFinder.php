<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;

class PropertyReflectionFinder
{

	/**
	 * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
	 * @param \PHPStan\Analyser\Scope $scope
	 * @return FoundPropertyReflection[]
	 */
	public function findPropertyReflectionsFromNode($propertyFetch, Scope $scope): array
	{
		if ($propertyFetch instanceof \PhpParser\Node\Expr\PropertyFetch) {
			if ($propertyFetch->name instanceof \PhpParser\Node\Identifier) {
				$names = [$propertyFetch->name->name];
			} else {
				$names = array_map(static function (ConstantStringType $name): string {
					return $name->getValue();
				}, TypeUtils::getConstantStrings($scope->getType($propertyFetch->name)));
			}

			$reflections = [];
			$propertyHolderType = $scope->getType($propertyFetch->var);
			$fetchedOnThis = $propertyHolderType instanceof ThisType && $scope->isInClass();
			foreach ($names as $name) {
				$reflection = $this->findPropertyReflection(
					$propertyHolderType,
					$name,
					$propertyFetch->name instanceof Expr ? $scope->filterByTruthyValue(new Expr\BinaryOp\Identical(
						$propertyFetch->name,
						new String_($name)
					)) : $scope,
					$fetchedOnThis
				);
				if ($reflection === null) {
					continue;
				}

				$reflections[] = $reflection;
			}

			return $reflections;
		}

		if ($propertyFetch->class instanceof \PhpParser\Node\Name) {
			$propertyHolderType = $scope->resolveTypeByName($propertyFetch->class);
		} else {
			$propertyHolderType = $scope->getType($propertyFetch->class);
		}

		$fetchedOnThis = $propertyHolderType instanceof ThisType && $scope->isInClass();

		if ($propertyFetch->name instanceof VarLikeIdentifier) {
			$names = [$propertyFetch->name->name];
		} else {
			$names = array_map(static function (ConstantStringType $name): string {
				return $name->getValue();
			}, TypeUtils::getConstantStrings($scope->getType($propertyFetch->name)));
		}

		$reflections = [];
		foreach ($names as $name) {
			$reflection = $this->findPropertyReflection(
				$propertyHolderType,
				$name,
				$propertyFetch->name instanceof Expr ? $scope->filterByTruthyValue(new Expr\BinaryOp\Identical(
					$propertyFetch->name,
					new String_($name)
				)) : $scope,
				$fetchedOnThis
			);
			if ($reflection === null) {
				continue;
			}

			$reflections[] = $reflection;
		}

		return $reflections;
	}

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
			$propertyHolderType = $scope->resolveTypeByName($propertyFetch->class);
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
			$scope,
			$propertyName,
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
