<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\Ternary>
 */
class TernaryOperatorConstantConditionRule implements \PHPStan\Rules\Rule
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
		return \PhpParser\Node\Expr\Ternary::class;
	}

	public function processNode(
		\PhpParser\Node $node,
		\PHPStan\Analyser\Scope $scope
	): array
	{
		$exprType = $this->helper->getBooleanType($scope, $node->cond);
		if ($exprType instanceof ConstantBooleanType) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Ternary operator condition is always %s.',
					$exprType->getValue() ? 'true' : 'false'
				))->build(),
			];
		}

		return [];
	}

}
