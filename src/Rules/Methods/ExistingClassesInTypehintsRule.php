<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\FunctionDefinitionCheck;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
class ExistingClassesInTypehintsRule implements Rule
{

	public function __construct(private FunctionDefinitionCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodReflection = $scope->getFunction();
		if (!$methodReflection instanceof PhpMethodFromParserNodeReflection) {
			throw new ShouldNotHappenException();
		}
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$className = SprintfHelper::escapeFormatString($scope->getClassReflection()->getDisplayName());
		$methodName = SprintfHelper::escapeFormatString($methodReflection->getName());

		return $this->check->checkClassMethod(
			$methodReflection,
			$node->getOriginalNode(),
			sprintf(
				'Parameter $%%s of method %s::%s() has invalid type %%s.',
				$className,
				$methodName,
			),
			sprintf(
				'Method %s::%s() has invalid return type %%s.',
				$className,
				$methodName,
			),
			sprintf('Method %s::%s() uses native union types but they\'re supported only on PHP 8.0 and later.', $className, $methodName),
			sprintf('Template type %%s of method %s::%s() is not referenced in a parameter.', $className, $methodName),
			sprintf(
				'Parameter $%%s of method %s::%s() has unresolvable native type.',
				$className,
				$methodName,
			),
			sprintf(
				'Method %s::%s() has unresolvable native return type.',
				$className,
				$methodName,
			),
		);
	}

}
