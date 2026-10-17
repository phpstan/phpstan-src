<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function sprintf;

/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
#[RegisteredRule(level: 4)]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/TooWideFunctionReturnTypehintRule.cpp')]
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

		return $this->check->checkFunctionReturnType(
			$node,
			$function->getNativeReturnType(),
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
