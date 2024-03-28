<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use function array_map;

class PropertyReflectionFinder
{

	/**
	 * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $propertyFetch
	 * @return FoundPropertyReflection[]
	 */
	public function findPropertyReflectionsFromNode($propertyFetch, Scope $scope): array
	{
		if ($propertyFetch instanceof Node\Expr\PropertyFetch) {
			if ($propertyFetch->name instanceof Node\Identifier) {
				$names = [$propertyFetch->name->name];
			} else {
				$names = array_map(static fn (ConstantStringType $name): string => $name->getValue(), $scope->getType($propertyFetch->name)->getConstantStrings());
			}

			$reflections = [];
			$propertyHolderType = $scope->getType($propertyFetch->var);
			foreach ($names as $name) {
				$reflection = $this->findPropertyReflection(
					$propertyHolderType,
					$name,
					$propertyFetch->name instanceof Expr ? $scope->filterByTruthyValue(new Expr\BinaryOp\Identical(
						$propertyFetch->name,
						new String_($name),
					)) : $scope,
				);
				if ($reflection === null) {
					continue;
				}

				$reflections[] = $reflection;
			}

			return $reflections;
		}

		if ($propertyFetch->class instanceof Node\Name) {
			$propertyHolderType = $scope->resolveTypeByName($propertyFetch->class);
		} else {
			$propertyHolderType = $scope->getType($propertyFetch->class);
		}

		if ($propertyFetch->name instanceof VarLikeIdentifier) {
			$names = [$propertyFetch->name->name];
		} else {
			$names = array_map(static fn (ConstantStringType $name): string => $name->getValue(), $scope->getType($propertyFetch->name)->getConstantStrings());
		}

		$reflections = [];
		foreach ($names as $name) {
			$reflection = $this->findPropertyReflection(
				$propertyHolderType,
				$name,
				$propertyFetch->name instanceof Expr ? $scope->filterByTruthyValue(new Expr\BinaryOp\Identical(
					$propertyFetch->name,
					new String_($name),
				)) : $scope,
			);
			if ($reflection === null) {
				continue;
			}

			$reflections[] = $reflection;
		}

		return $reflections;
	}

	/**
	 * @param Node\Expr\PropertyFetch|Node\Expr\StaticPropertyFetch $propertyFetch
	 */
	public function findPropertyReflectionFromNode($propertyFetch, Scope $scope): ?FoundPropertyReflection
	{
		if ($propertyFetch instanceof Node\Expr\PropertyFetch) {
			if (!$propertyFetch->name instanceof Node\Identifier) {
				return null;
			}
			$propertyHolderType = $scope->getType($propertyFetch->var);
			return $this->findPropertyReflection($propertyHolderType, $propertyFetch->name->name, $scope);
		}

		if (!$propertyFetch->name instanceof Node\Identifier) {
			return null;
		}

		if ($propertyFetch->class instanceof Node\Name) {
			$propertyHolderType = $propertyFetch->class->toLowerString() === 'static'
				? $scope->getType(new Expr\ClassConstFetch($propertyFetch->class, 'class'))->getClassStringObjectType()
				: $scope->resolveTypeByName($propertyFetch->class);
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
			$originalProperty->getWritableType(),
		);
	}

}
