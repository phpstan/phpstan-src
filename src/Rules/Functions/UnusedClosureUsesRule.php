<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\UnusedFunctionParametersCheck;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Closure>
 */
class UnusedClosureUsesRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\UnusedFunctionParametersCheck $check;

	public function __construct(UnusedFunctionParametersCheck $check)
	{
		$this->check = $check;
	}

	public function getNodeType(): string
	{
		return Node\Expr\Closure::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->uses) === 0) {
			return [];
		}

		return $this->check->getUnusedParameters(
			$scope,
			array_map(static function (Node\Expr\ClosureUse $use): string {
				if (!is_string($use->var->name)) {
					throw new \PHPStan\ShouldNotHappenException();
				}
				return $use->var->name;
			}, $node->uses),
			$node->stmts,
			'Anonymous function has an unused use $%s.',
			'anonymousFunction.unusedUse',
			[
				'statementDepth' => $node->getAttribute('statementDepth'),
				'statementOrder' => $node->getAttribute('statementOrder'),
				'depth' => $node->getAttribute('expressionDepth'),
				'order' => $node->getAttribute('expressionOrder'),
			]
		);
	}

}
