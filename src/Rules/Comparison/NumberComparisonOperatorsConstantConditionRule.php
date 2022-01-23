<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
class NumberComparisonOperatorsConstantConditionRule implements Rule
{

	public function getNodeType(): string
	{
		return BinaryOp::class;
	}

	public function processNode(
		Node $node,
		Scope $scope,
	): array
	{
		if (
			!$node instanceof BinaryOp\Greater
			&& !$node instanceof BinaryOp\GreaterOrEqual
			&& !$node instanceof BinaryOp\Smaller
			&& !$node instanceof BinaryOp\SmallerOrEqual
		) {
			return [];
		}

		$exprType = $scope->getType($node);
		if ($exprType instanceof ConstantBooleanType) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Comparison operation "%s" between %s and %s is always %s.',
					$node->getOperatorSigil(),
					$scope->getType($node->left)->describe(VerbosityLevel::value()),
					$scope->getType($node->right)->describe(VerbosityLevel::value()),
					$exprType->getValue() ? 'true' : 'false',
				))->build(),
			];
		}

		return [];
	}

}
