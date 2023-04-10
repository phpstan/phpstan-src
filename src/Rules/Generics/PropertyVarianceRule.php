<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Type\Generic\TemplateTypeVariance;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
class PropertyVarianceRule implements Rule
{

	public function __construct(
		private VarianceCheck $varianceCheck,
		private bool $readOnlyByPhpDoc,
	)
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

		$variance = $node->isReadOnly() || ($this->readOnlyByPhpDoc && $node->isReadOnlyByPhpDoc())
			? TemplateTypeVariance::createCovariant()
			: TemplateTypeVariance::createInvariant();

		return $this->varianceCheck->check(
			$variance,
			$propertyReflection->getReadableType(),
			sprintf('in property %s::$%s', SprintfHelper::escapeFormatString($classReflection->getDisplayName()), SprintfHelper::escapeFormatString($node->getName())),
		);
	}

}
