<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\Expr\ForeachValueByRefExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Node\VariableAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<NodeAbstract>
 */
#[RegisteredRule(level: 1, enabledBy: '%featureToggles.assignToByRefForeachExpr%')]
final class AssignToByRefExprFromForeachRule implements Rule
{

	public function __construct(private ExprPrinter $printer)
	{
	}

	public function getNodeType(): string
	{
		return NodeAbstract::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof VariableAssignNode) {
			$expr = $node->getVariable();
		} elseif ($node instanceof PropertyAssignNode) {
			$expr = $node->getPropertyFetch();
		} else {
			return [];
		}

		if ($scope->hasExpressionType(new ForeachValueByRefExpr($expr))->no()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Assign to %s overwrites the last element from array.',
				$this->printer->printExpr($expr),
			))->identifier('assign.byRefForeachExpr')
				->tip('Unset it right after foreach to avoid this problem.')->build(),
		];
	}

}
