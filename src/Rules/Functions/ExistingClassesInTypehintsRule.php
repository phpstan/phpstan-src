<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InFunctionNode;
use PHPStan\Rules\FunctionDefinitionCheck;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<InFunctionNode>
 */
final class ExistingClassesInTypehintsRule implements Rule
{

	public function __construct(private FunctionDefinitionCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return InFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionName = SprintfHelper::escapeFormatString($node->getFunctionReflection()->getName());

		return $this->check->checkFunction(
			$node->getOriginalNode(),
			$node->getFunctionReflection(),
			sprintf(
				'Parameter $%%s of function %s() has invalid type %%s.',
				$functionName,
			),
			sprintf(
				'Function %s() has invalid return type %%s.',
				$functionName,
			),
			sprintf('Function %s() uses native union types but they\'re supported only on PHP 8.0 and later.', $functionName),
			sprintf('Template type %%s of function %s() is not referenced in a parameter.', $functionName),
			sprintf(
				'Parameter $%%s of function %s() has unresolvable native type.',
				$functionName,
			),
			sprintf(
				'Function %s() has unresolvable native return type.',
				$functionName,
			),
		);
	}

}
