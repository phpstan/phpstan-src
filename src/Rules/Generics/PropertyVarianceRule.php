<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
class PropertyVarianceRule implements Rule
{

	public function __construct(private VarianceCheck $varianceCheck)
	{
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $scope->getClassReflection();
		if (!$classReflection instanceof ClassReflection) {
			return [];
		}

		if (!$classReflection->hasNativeProperty($node->getName())) {
			return [];
		}

		$propertyReflection = $classReflection->getNativeProperty($node->getName());

		if ($propertyReflection->isPrivate()) {
			return [];
		}

		return $this->varianceCheck->checkProperty(
			$propertyReflection,
			sprintf('in property %s::$%s', SprintfHelper::escapeFormatString($classReflection->getDisplayName()), SprintfHelper::escapeFormatString($node->getName())),
			$propertyReflection->isReadOnly(),
		);
	}

}
