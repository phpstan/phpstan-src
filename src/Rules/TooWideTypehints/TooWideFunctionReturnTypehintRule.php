<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
final class TooWideFunctionReturnTypehintRule implements Rule
{

	public function __construct(
		private TooWideTypeCheck $check,
	)
	{
	}

	public function getNodeType(): string
	{
		return FunctionReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$function = $node->getFunctionReflection();

		return $this->check->checkFunction(
			$node,
			$function->getReturnType(),
			$function->getPhpDocReturnType(),
			sprintf(
				'Function %s()',
				$function->getName(),
			),
			false,
			$scope,
		);
	}

}
