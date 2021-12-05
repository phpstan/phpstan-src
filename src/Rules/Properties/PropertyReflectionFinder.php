<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

class PropertyReflectionFinder
{

	/**
	 * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
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
			foreach ($names as $name) {
				$reflection = $this->findPropertyReflection(
					$propertyHolderType,
					$name,
					$propertyFetch->name instanceof Expr ? $scope->filterByTruthyValue(new Expr\BinaryOp\Identical(
						$propertyFetch->name,
						new String_($name)
					)) : $scope
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
				)) : $scope
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
	 */
	public function findPropertyReflectionFromNode($propertyFetch, Scope $scope): ?FoundPropertyReflection
	{
		if ($propertyFetch instanceof \PhpParser\Node\Expr\PropertyFetch) {
			if (!$propertyFetch->name instanceof \PhpParser\Node\Identifier) {
				return null;
			}
			$propertyHolderType = $scope->getType($propertyFetch->var);
			return $this->findPropertyReflection($propertyHolderType, $propertyFetch->name->name, $scope);
		}

		if (!$propertyFetch->name instanceof \PhpParser\Node\Identifier) {
			return null;
		}

		if ($propertyFetch->class instanceof \PhpParser\Node\Name) {
			$propertyHolderType = $scope->resolveTypeByName($propertyFetch->class);
		} else {
			$propertyHolderType = $scope->getType($propertyFetch->class);
		}

		return $this->findPropertyReflection($propertyHolderType, $propertyFetch->name->name, $scope);
	}

	private function findPropertyReflection(Type $propertyHolderType, string $propertyName, Scope $scope): ?FoundPropertyReflection
	{
		if (!$propertyHolderType->hasProperty($propertyName)->yes()) {
			return null;
		}

		$originalProperty = $propertyHolderType->getProperty($propertyName, $scope);

		return new FoundPropertyReflection(
			$originalProperty,
			$scope,
			$propertyName,
			$originalProperty->getReadableType(),
			$originalProperty->getWritableType()
		);
	}

}
