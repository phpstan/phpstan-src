<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\BinaryOp\BooleanAnd>
 */
class BooleanAndConstantConditionRule implements \PHPStan\Rules\Rule
{

	private ConstantConditionRuleHelper $helper;

	private bool $treatPhpDocTypesAsCertain;

	public function __construct(
		ConstantConditionRuleHelper $helper,
		bool $treatPhpDocTypesAsCertain
	)
	{
		$this->helper = $helper;
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
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
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		if ($leftType instanceof ConstantBooleanType) {
			$addTipLeft = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node, $tipText): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->left);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip($tipText);
			};
			$errors[] = $addTipLeft(RuleErrorBuilder::message(sprintf(
				'Left side of && is always %s.',
				$leftType->getValue() ? 'true' : 'false'
			)))->line($node->left->getLine())->build();
		}

		$rightType = $this->helper->getBooleanType(
			$scope->filterByTruthyValue($node->left),
			$node->right
		);
		if ($rightType instanceof ConstantBooleanType) {
			$addTipRight = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node, $tipText): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType(
					$scope->doNotTreatPhpDocTypesAsCertain()->filterByTruthyValue($node->left),
					$node->right
				);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip($tipText);
			};
			$errors[] = $addTipRight(RuleErrorBuilder::message(sprintf(
				'Right side of && is always %s.',
				$rightType->getValue() ? 'true' : 'false'
			)))->line($node->right->getLine())->build();
		}

		if (count($errors) === 0) {
			$nodeType = $scope->getType($node);
			if ($nodeType instanceof ConstantBooleanType) {
				$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node, $tipText): RuleErrorBuilder {
					if (!$this->treatPhpDocTypesAsCertain) {
						return $ruleErrorBuilder;
					}

					$booleanNativeType = $scope->doNotTreatPhpDocTypesAsCertain()->getType($node);
					if ($booleanNativeType instanceof ConstantBooleanType) {
						return $ruleErrorBuilder;
					}

					return $ruleErrorBuilder->tip($tipText);
				};

				$errors[] = $addTip(RuleErrorBuilder::message(sprintf(
					'Result of && is always %s.',
					$nodeType->getValue() ? 'true' : 'false'
				)))->build();
			}
		}

		return $errors;
	}

}
