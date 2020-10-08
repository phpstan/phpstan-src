<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
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
	 * @return FoundPropertyReflection[]
	 */
	public function findPropertyReflectionsFromNode($propertyFetch, Scope $scope): array
	{
		$foundPropertyNamesAndTypes = $this->findPropertyNamesAndTypes($propertyFetch, $scope);

		if (count($foundPropertyNamesAndTypes) > 0) {
			$propertyReflections = $this->findPropertyReflectionFromDynamicPropertyNamesAndTypes($propertyFetch, $scope, $foundPropertyNamesAndTypes);
		} else {
			$propertyReflection = $this->findPropertyReflectionFromNode($propertyFetch, $scope);
			if ($propertyReflection === null) {
				return [];
			}
			$propertyReflections = [$propertyReflection];
		}

		return $propertyReflections;
	}

	/**
	 * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
	 * @param \PHPStan\Analyser\Scope $scope
	 * @param array<string, \PHPStan\Type\Type> $dynamicPropertyNamesAndTypes
	 * @return FoundPropertyReflection[]
	 */
	private function findPropertyReflectionFromDynamicPropertyNamesAndTypes($propertyFetch, Scope $scope, array $dynamicPropertyNamesAndTypes): array
	{
		$foundPropertyReflections = [];

		if ($propertyFetch instanceof \PhpParser\Node\Expr\PropertyFetch) {
			foreach ($dynamicPropertyNamesAndTypes as $propertyName => $propertyType) {
				$propertyHolderType = $scope->getType($propertyFetch->var);
				$fetchedOnThis = $propertyHolderType instanceof ThisType && $scope->isInClass();

				$foundPropertyReflectionTmp = $this->findPropertyReflection($propertyHolderType, $propertyName, $scope, $fetchedOnThis, $propertyType);
				if ($foundPropertyReflectionTmp === null) {
					continue;
				}

				$foundPropertyReflections[] = $foundPropertyReflectionTmp;
			}
		} else {
			foreach ($dynamicPropertyNamesAndTypes as $propertyName => $propertyType) {
				if ($propertyFetch->class instanceof \PhpParser\Node\Name) {
					$propertyHolderType = new ObjectType($scope->resolveName($propertyFetch->class));
				} else {
					$propertyHolderType = $scope->getType($propertyFetch->class);
				}

				$fetchedOnThis = $propertyHolderType instanceof ThisType && $scope->isInClass();

				$foundPropertyReflectionTmp = $this->findPropertyReflection($propertyHolderType, $propertyName, $scope, $fetchedOnThis, $propertyType);
				if ($foundPropertyReflectionTmp === null) {
					continue;
				}

				$foundPropertyReflections[] = $foundPropertyReflectionTmp;
			}
		}

		return $foundPropertyReflections;
	}

	/**
	 * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
	 * @param \PHPStan\Analyser\Scope $scope
	 * @return FoundPropertyReflection|null
	 *
	 * @internal Use PropertyReflectionFinder::findPropertyReflectionsFromNode() instead.
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

	private function findPropertyReflection(Type $propertyHolderType, string $propertyName, Scope $scope, bool $fetchedOnThis, ?\PHPStan\Type\Type $overwriteType = null): ?FoundPropertyReflection
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
			$writableType,
			$overwriteType,
			$propertyName
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

	/**
	 * @param Node\Expr $node
	 * @param Scope $scope
	 * @return array<string, \PHPStan\Type\Type>
	 */
	private function findPropertyNamesAndTypes(Node\Expr $node, Scope $scope): array
	{
		$return = [];

		$assignedVariable = $node->getAttribute('next');
		if ($assignedVariable === null) {
			return [];
		}

		$expressionAssignNode = $node->getAttribute('parent');
		if (!$expressionAssignNode instanceof \PhpParser\Node\Expr\Assign
			&& !$expressionAssignNode instanceof \PhpParser\Node\Expr\AssignOp
		) {
			return [];
		}

		$expressionPropertyFetchNode = $expressionAssignNode->var;
		if (
			!$expressionPropertyFetchNode instanceof \PhpParser\Node\Expr\PropertyFetch
			&& !$expressionPropertyFetchNode instanceof \PhpParser\Node\Expr\StaticPropertyFetch
		) {
			return [];
		}

		$variableNode = $expressionPropertyFetchNode->name;
		if (!$variableNode instanceof \PhpParser\Node\Expr\Variable) {
			return [];
		}

		$variableType = $scope->getType($variableNode);
		if (!$variableType instanceof \PHPStan\Type\UnionType
			&&
			!$variableType instanceof \PHPStan\Type\Constant\ConstantStringType
		) {
			return [];
		}

		$iterableElementTypes = $this->findArrayShapeTypesFromAssignmet($expressionAssignNode, $assignedVariable, $scope);

		$typeFromAssignedVariable = $scope->getType($assignedVariable);

		if ($variableType instanceof \PHPStan\Type\Constant\ConstantStringType) {
			$name = $variableType->getValue();
			if (isset($iterableElementTypes[$name])) {
				$return[$name] = $iterableElementTypes[$name];
			} else {
				$return[$name] = $typeFromAssignedVariable;
			}

			return $return;
		}

		foreach ($variableType->getTypes() as $type) {
			if (!($type instanceof \PHPStan\Type\Constant\ConstantStringType)) {
				continue;
			}

			$name = $type->getValue();
			if (isset($iterableElementTypes[$name])) {
				$return[$name] = $iterableElementTypes[$name];
			} else {
				$return[$name] = $typeFromAssignedVariable;
			}
		}

		return $return;
	}

	/**
	 * @param \PhpParser\Node\Expr\Assign|\PhpParser\Node\Expr\AssignOp $iterableNode
	 * @param Node $assignedVariable
	 * @param Scope $scope
	 * @return array<string, Type>
	 */
	private function findArrayShapeTypesFromAssignmet(\PhpParser\Node\Expr $iterableNode, Node $assignedVariable, Scope $scope): array
	{
		$iterableType = null;
		$maxCounter = 0;
		while ($iterableNode !== null && $maxCounter <= 256) {
			$maxCounter++;

			$iterableNode = $iterableNode->getAttribute('parent');
			if (!$iterableNode instanceof \PhpParser\Node\Stmt\Foreach_) {
				continue;
			}

			if (!$iterableNode->valueVar instanceof \PhpParser\Node\Expr\Variable
				|| !$assignedVariable instanceof \PhpParser\Node\Expr\Variable
			) {
				continue;
			}

			// check if the assigned variable comes from correct foreach loop
			if ($iterableNode->valueVar->name !== $assignedVariable->name) {
				continue;
			}

			$iterableType = $scope->getType($iterableNode->expr);
			if ($iterableType instanceof \PHPStan\Type\Constant\ConstantArrayType) {
				break;
			}
		}
		if (!$iterableType instanceof \PHPStan\Type\Constant\ConstantArrayType) {
			return [];
		}

		$iterableTypeKeyTypes = $iterableType->getKeyTypes();
		if (count($iterableTypeKeyTypes) === 0) {
			return [];
		}

		$iterableTypeValueTypes = $iterableType->getValueTypes();
		if (count($iterableTypeValueTypes) === 0) {
			return [];
		}

		$iterableElementTypes = [];
		foreach ($iterableTypeKeyTypes as $iterableTypeKeyIndex => $iterableTypeKeyValue) {
			if (!($iterableTypeKeyValue instanceof \PHPStan\Type\Constant\ConstantStringType)) {
				continue;
			}

			$iterableElementTypes[$iterableTypeKeyValue->getValue()] = $iterableTypeValueTypes[$iterableTypeKeyIndex];
		}

		return $iterableElementTypes;
	}

}
