<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use function array_map;
use function count;

#[AutowiredService]
final class PropertyReflectionFinder
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
				$reflection = $this->findInstancePropertyReflection(
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
			$reflection = $this->findStaticPropertyReflection(
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
			$propertyHolderType = $scope->getType($propertyFetch->var);
			if ($propertyFetch->name instanceof Node\Identifier) {
				return $this->findInstancePropertyReflection($propertyHolderType, $propertyFetch->name->name, $scope);
			}

			$nameType = $scope->getType($propertyFetch->name);
			$nameTypeConstantStrings = $nameType->getConstantStrings();
			if (count($nameTypeConstantStrings) === 1) {
				return $this->findInstancePropertyReflection($propertyHolderType, $nameTypeConstantStrings[0]->getValue(), $scope);
			}

			return null;
		}

		if (!$propertyFetch->name instanceof Node\Identifier) {
			return null;
		}

		if ($propertyFetch->class instanceof Node\Name) {
			$propertyHolderType = $scope->resolveTypeByName($propertyFetch->class);
		} else {
			$propertyHolderType = $scope->getType($propertyFetch->class);
		}

		return $this->findStaticPropertyReflection($propertyHolderType, $propertyFetch->name->name, $scope);
	}

	private function findInstancePropertyReflection(Type $propertyHolderType, string $propertyName, Scope $scope): ?FoundPropertyReflection
	{
		if (!$propertyHolderType->hasInstanceProperty($propertyName)->yes()) {
			return null;
		}

		$originalProperty = $propertyHolderType->getInstanceProperty($propertyName, $scope);

		return new FoundPropertyReflection(
			$originalProperty,
			$scope,
			$propertyName,
			$originalProperty->getReadableType(),
			$originalProperty->getWritableType(),
		);
	}

	private function findStaticPropertyReflection(Type $propertyHolderType, string $propertyName, Scope $scope): ?FoundPropertyReflection
	{
		if (!$propertyHolderType->hasStaticProperty($propertyName)->yes()) {
			return null;
		}

		$originalProperty = $propertyHolderType->getStaticProperty($propertyName, $scope);

		return new FoundPropertyReflection(
			$originalProperty,
			$scope,
			$propertyName,
			$originalProperty->getReadableType(),
			$originalProperty->getWritableType(),
		);
	}

}
