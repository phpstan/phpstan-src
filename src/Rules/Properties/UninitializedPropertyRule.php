<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

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
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$classReflection = $scope->getClassReflection();
		[$properties, $prematureAccess] = $node->getUninitializedProperties($scope);

		$errors = [];
		foreach ($properties as $propertyName => $propertyNode) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Class %s has an uninitialized property $%s. Give it default value or assign it in the constructor.',
				$classReflection->getDisplayName(),
				$propertyName
			))->line($propertyNode->getLine())->build();
		}

		foreach ($prematureAccess as [$propertyName, $line]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Access to an uninitialized property %s::$%s.',
				$classReflection->getDisplayName(),
				$propertyName
			))->line($line)->build();
		}

		return $errors;
	}

}
