<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\VariableWritesNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedParametersCheck;
use function count;
use function sprintf;

/**
 * @implements Rule<VariableWritesNode>
 */
final class UnusedFunctionParametersRule implements Rule
{

	public function __construct(private UnusedParametersCheck $check)
	{
	}

	public function getNodeType(): string
	{
		return VariableWritesNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$originalNode = $node->getFunctionLike();
		if (!$originalNode instanceof Node\Stmt\Function_) {
			return [];
		}
		if ($this->check->isNoOpBody($originalNode->stmts)) {
			return [];
		}
		if (count($originalNode->params) === 0) {
			return [];
		}
		$function = $scope->getFunction();
		if ($function === null) {
			return [];
		}

		return $this->check->getUnusedParameterErrors(
			$node,
			$function,
			$originalNode->params,
			sprintf('Function %s() has an unused parameter $%%s.', SprintfHelper::escapeFormatString($function->getName())),
			'function.unusedParameter',
			true,
			sprintf('Function %s() has a parameter $%%s that only flows into values that are never used.', SprintfHelper::escapeFormatString($function->getName())),
			'function.unusedParameterFlow',
		);
	}

}
