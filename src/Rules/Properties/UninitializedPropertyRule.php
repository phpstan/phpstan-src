<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<ClassPropertiesNode>
 */
class UninitializedPropertyRule implements Rule
{

	public function __construct(
		private ReadWritePropertiesExtensionProvider $extensionProvider,
		private ConstructorsHelper $constructorsHelper,
	)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertiesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}
		$classReflection = $scope->getClassReflection();
		[$properties, $prematureAccess] = $node->getUninitializedProperties($scope, $this->constructorsHelper->getConstructors($classReflection), $this->extensionProvider->getExtensions());

		$errors = [];
		foreach ($properties as $propertyName => $propertyNode) {
			if ($propertyNode->isReadOnly() || $propertyNode->isReadOnlyByPhpDoc()) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Class %s has an uninitialized property $%s. Give it default value or assign it in the constructor.',
				$classReflection->getDisplayName(),
				$propertyName,
			))->line($propertyNode->getLine())->build();
		}

		foreach ($prematureAccess as [$propertyName, $line, $propertyNode]) {
			if ($propertyNode->isReadOnly() || $propertyNode->isReadOnlyByPhpDoc()) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Access to an uninitialized property %s::$%s.',
				$classReflection->getDisplayName(),
				$propertyName,
			))->line($line)->build();
		}

		return $errors;
	}

}
