<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\UnusedFunctionParametersCheck;
use function array_map;
use function count;

/**
 * @implements Rule<Node\Expr\Closure>
 */
#[RegisteredRule(level: 1)]
final class UnusedClosureUsesRule implements Rule
{

	public function __construct(private UnusedFunctionParametersCheck $check)
	{
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
			array_map(static fn (Node\ClosureUse $use): Node\Expr\Variable => $use->var, $node->uses),
			$node->stmts,
			'Anonymous function has an unused use $%s.',
			'closure.unusedUse',
		);
	}

}
