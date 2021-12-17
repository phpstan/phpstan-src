<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
class StrictComparisonOfDifferentTypesRule implements Rule
{

	private bool $checkAlwaysTrueStrictComparison;

	public function __construct(bool $checkAlwaysTrueStrictComparison)
	{
		$this->checkAlwaysTrueStrictComparison = $checkAlwaysTrueStrictComparison;
	}

	public function getNodeType(): string
	{
		return Node\Expr\BinaryOp::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Node\Expr\BinaryOp\Identical && !$node instanceof Node\Expr\BinaryOp\NotIdentical) {
			return [];
		}

		$nodeType = $scope->getType($node);
		if (!$nodeType instanceof ConstantBooleanType) {
			return [];
		}

		$leftType = $scope->getType($node->left);
		$rightType = $scope->getType($node->right);

		if (!$nodeType->getValue()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Strict comparison using %s between %s and %s will always evaluate to false.',
					$node instanceof Node\Expr\BinaryOp\Identical ? '===' : '!==',
					$leftType->describe(VerbosityLevel::value()),
					$rightType->describe(VerbosityLevel::value()),
				))->build(),
			];
		} elseif ($this->checkAlwaysTrueStrictComparison) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Strict comparison using %s between %s and %s will always evaluate to true.',
					$node instanceof Node\Expr\BinaryOp\Identical ? '===' : '!==',
					$leftType->describe(VerbosityLevel::value()),
					$rightType->describe(VerbosityLevel::value()),
				))->build(),
			];
		}

		return [];
	}

}
