<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
final class MethodSignatureVarianceRule implements Rule
{

	public function __construct(private VarianceCheck $varianceCheck)
	{
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$method = $node->getMethodReflection();

		return $this->varianceCheck->checkParametersAcceptor(
			ParametersAcceptorSelector::selectSingle($method->getVariants()),
			sprintf('in parameter %%s of method %s::%s()', SprintfHelper::escapeFormatString($method->getDeclaringClass()->getDisplayName()), SprintfHelper::escapeFormatString($method->getName())),
			sprintf('in param-out type of parameter %%s of method %s::%s()', SprintfHelper::escapeFormatString($method->getDeclaringClass()->getDisplayName()), SprintfHelper::escapeFormatString($method->getName())),
			sprintf('in return type of method %s::%s()', $method->getDeclaringClass()->getDisplayName(), $method->getName()),
			sprintf('in method %s::%s()', $method->getDeclaringClass()->getDisplayName(), $method->getName()),
			$method->isStatic(),
			$method->isPrivate() || $method->getName() === '__construct',
			'method',
		);
	}

}
