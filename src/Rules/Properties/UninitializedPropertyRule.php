<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\Method\MethodCall;
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

		$errors = [];
		if ($constructor !== null) {
			$classType = new ObjectType($scope->getClassReflection()->getName());
			$methodsCalledFromConstructor = $this->getMethodsCalledFromConstructor($classType, $node->getMethodCalls(), [$constructor->getName()]);
			foreach ($node->getPropertyUsages() as $usage) {
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
				if (!in_array($function->getName(), $methodsCalledFromConstructor, true)) {
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

				if ($usage instanceof PropertyWrite) {
					unset($properties[$propertyName]);
				} elseif (array_key_exists($propertyName, $properties)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Access to an uninitialized property %s::$%s.',
						$classReflection->getDisplayName(),
						$propertyName
					))->line($fetch->getLine())->build();
				}
			}
		}

		foreach ($properties as $propertyName => $propertyNode) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Class %s has an uninitialized property $%s. Give it default value or assign it in the constructor.',
				$classReflection->getDisplayName(),
				$propertyName
			))->line($propertyNode->getLine())->build();
		}

		return $errors;
	}

	/**
	 * @param ObjectType $classType
	 * @param MethodCall[] $methodCalls
	 * @param string[] $methods
	 * @return string[]
	 */
	private function getMethodsCalledFromConstructor(
		ObjectType $classType,
		array $methodCalls,
		array $methods
	): array
	{
		$originalCount = count($methods);
		foreach ($methodCalls as $methodCall) {
			$methodCallNode = $methodCall->getNode();
			if (!$methodCallNode->name instanceof Node\Identifier) {
				continue;
			}
			$callScope = $methodCall->getScope();
			$calledOnType = $callScope->getType($methodCallNode->var);
			if ($classType->isSuperTypeOf($calledOnType)->no()) {
				continue;
			}
			if ($calledOnType instanceof MixedType) {
				continue;
			}
			$methodName = $methodCallNode->name->toString();
			if (in_array($methodName, $methods, true)) {
				continue;
			}
			$inMethod = $callScope->getFunction();
			if (!$inMethod instanceof MethodReflection) {
				continue;
			}
			if (!in_array($inMethod->getName(), $methods, true)) {
				continue;
			}
			$methods[] = $methodName;
		}

		if ($originalCount === count($methods)) {
			return $methods;
		}

		return $this->getMethodsCalledFromConstructor($classType, $methodCalls, $methods);
	}

}
