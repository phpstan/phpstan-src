<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InFunctionNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<InFunctionNode>
 */
final class FunctionSignatureVarianceRule implements Rule
{

	public function __construct(private VarianceCheck $varianceCheck)
	{
	}

	public function getNodeType(): string
	{
		return InFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionReflection = $node->getFunctionReflection();
		$functionName = $functionReflection->getName();

		return $this->varianceCheck->checkParametersAcceptor(
			ParametersAcceptorSelector::selectSingle($functionReflection->getVariants()),
			sprintf('in parameter %%s of function %s()', SprintfHelper::escapeFormatString($functionName)),
			sprintf('in param-out type of parameter %%s of function %s()', SprintfHelper::escapeFormatString($functionName)),
			sprintf('in return type of function %s()', $functionName),
			sprintf('in function %s()', $functionName),
			false,
			false,
			'function',
		);
	}

}
