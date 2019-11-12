<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\BinaryOp\BooleanAnd>
 */
class BooleanAndConstantConditionRule implements \PHPStan\Rules\Rule
{

	/** @var ConstantConditionRuleHelper */
	private $helper;

	public function __construct(
		ConstantConditionRuleHelper $helper
	)
	{
		$this->helper = $helper;
	}

	public function getNodeType(): string
	{
		return \PhpParser\Node\Expr\BinaryOp\BooleanAnd::class;
	}

	public function processNode(
		\PhpParser\Node $node,
		\PHPStan\Analyser\Scope $scope
	): array
	{
		$errors = [];
		$leftType = $this->helper->getBooleanType($scope, $node->left);
		if ($leftType instanceof ConstantBooleanType) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Left side of && is always %s.',
				$leftType->getValue() ? 'true' : 'false'
			))->line($node->left->getLine())->build();
		}

		$rightType = $this->helper->getBooleanType(
			$scope->filterByTruthyValue($node->left),
			$node->right
		);
		if ($rightType instanceof ConstantBooleanType) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Right side of && is always %s.',
				$rightType->getValue() ? 'true' : 'false'
			))->line($node->right->getLine())->build();
		}

		if (count($errors) === 0) {
			$nodeType = $scope->getType($node);
			if ($nodeType instanceof ConstantBooleanType) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Result of && is always %s.',
					$nodeType->getValue() ? 'true' : 'false'
				))->build();
			}
		}

		return $errors;
	}

}
