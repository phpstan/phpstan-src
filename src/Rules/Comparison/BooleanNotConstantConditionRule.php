<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\BooleanNot>
 */
class BooleanNotConstantConditionRule implements \PHPStan\Rules\Rule
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
		return \PhpParser\Node\Expr\BooleanNot::class;
	}

	public function processNode(
		\PhpParser\Node $node,
		\PHPStan\Analyser\Scope $scope
	): array
	{
		$exprType = $this->helper->getBooleanType($scope, $node->expr);
		if ($exprType instanceof ConstantBooleanType) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Negated boolean expression is always %s.',
					$exprType->getValue() ? 'false' : 'true'
				))->line($node->expr->getLine())->build(),
			];
		}

		return [];
	}

}
