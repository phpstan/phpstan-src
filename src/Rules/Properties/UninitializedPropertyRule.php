<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Reflection\ConstructorsHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<ClassPropertiesNode>
 */
class UninitializedPropertyRule implements Rule
{

	public function __construct(
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
		$classReflection = $node->getClassReflection();
		[$properties, $prematureAccess] = $node->getUninitializedProperties($scope, $this->constructorsHelper->getConstructors($classReflection));

		$errors = [];
		foreach ($properties as $propertyName => $propertyNode) {
			if ($propertyNode->isReadOnly() || $propertyNode->isReadOnlyByPhpDoc()) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Class %s has an uninitialized property $%s. Give it default value or assign it in the constructor.',
				$classReflection->getDisplayName(),
				$propertyName,
			))
				->line($propertyNode->getLine())
				->identifier('property.uninitialized')
				->build();
		}

		foreach ($prematureAccess as [$propertyName, $line, $propertyNode, $file]) {
			if ($propertyNode->isReadOnly() || $propertyNode->isReadOnlyByPhpDoc()) {
				continue;
			}
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Access to an uninitialized property %s::$%s.',
				$classReflection->getDisplayName(),
				$propertyName,
			))
				->line($line)
				->file($file)
				->identifier('property.uninitialized')
				->build();
		}

		return $errors;
	}

}
