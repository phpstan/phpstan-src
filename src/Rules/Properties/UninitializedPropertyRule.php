<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\Property\PropertyWrite;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<ClassPropertiesNode>
 */
class UninitializedPropertyRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassPropertiesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->getClass() instanceof Node\Stmt\Class_) {
			return [];
		}
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$constructor = null;
		$classReflection = $scope->getClassReflection();
		if ($classReflection->hasConstructor()) {
			$constructor = $classReflection->getConstructor();
		}

		$properties = [];
		foreach ($node->getProperties() as $property) {
			if ($property->isStatic()) {
				continue;
			}
			if ($property->type === null) {
				continue;
			}
			foreach ($property->props as $prop) {
				if ($prop->default !== null) {
					continue;
				}
				$properties[$prop->name->toString()] = $property;
			}
		}

		if ($constructor !== null) {
			$classType = new ObjectType($scope->getClassReflection()->getName());
			foreach ($node->getPropertyUsages() as $usage) {
				if (!$usage instanceof PropertyWrite) {
					continue;
				}
				$usageScope = $usage->getScope();
				if ($usageScope->getFunction() === null) {
					continue;
				}
				$function = $usageScope->getFunction();
				if (!$function instanceof MethodReflection) {
					continue;
				}
				if ($function->getDeclaringClass()->getName() !== $classReflection->getName()) {
					continue;
				}
				if ($function->getName() !== $constructor->getName()) {
					continue;
				}

				$fetch = $usage->getFetch();
				if (!$fetch->name instanceof Node\Identifier) {
					continue;
				}
				$propertyName = $fetch->name->toString();
				if (!array_key_exists($propertyName, $properties)) {
					continue;
				}
				$fetchedOnType = $usageScope->getType($fetch->var);
				if ($classType->isSuperTypeOf($fetchedOnType)->no()) {
					continue;
				}
				if ($fetchedOnType instanceof MixedType) {
					continue;
				}

				unset($properties[$propertyName]);
			}
		}

		$errors = [];
		foreach ($properties as $propertyName => $propertyNode) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Class %s has an uninitialized property $%s. Give it default value or assign it in the constructor.',
				$classReflection->getDisplayName(),
				$propertyName
			))->line($propertyNode->getLine())->build();
		}

		return $errors;
	}

}
