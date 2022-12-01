<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PHPStan\Analyser\Scope;
use PHPStan\Node\BooleanAndNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function count;
use function sprintf;
use function strtoupper;

/**
 * @implements Rule<BooleanAndNode>
 */
class BooleanAndConstantConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		private bool $treatPhpDocTypesAsCertain,
		private bool $bleedingEdge,
	)
	{
	}

	public function getNodeType(): string
	{
		return BooleanAndNode::class;
	}

	public function processNode(
		Node $node,
		Scope $scope,
	): array
	{
		$errors = [];
		$originalNode = $node->getOriginalNode();
		$nodeText = $this->bleedingEdge ? $this->getOperatorDescription($originalNode) : '&&';
		$leftType = $this->helper->getBooleanType($scope, $originalNode->left);
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		if ($leftType instanceof ConstantBooleanType) {
			$addTipLeft = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $tipText, $originalNode): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $originalNode->left);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip($tipText);
			};
			$errors[] = $addTipLeft(RuleErrorBuilder::message(sprintf(
				'Left side of %s is always %s.',
				$nodeText,
				$leftType->getValue() ? 'true' : 'false',
			)))->line($originalNode->left->getLine())->build();
		}

		$rightScope = $node->getRightScope();
		$rightType = $this->helper->getBooleanType(
			$rightScope,
			$originalNode->right,
		);
		if ($rightType instanceof ConstantBooleanType) {
			$addTipRight = function (RuleErrorBuilder $ruleErrorBuilder) use ($rightScope, $originalNode, $tipText): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType(
					$rightScope->doNotTreatPhpDocTypesAsCertain(),
					$originalNode->right,
				);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip($tipText);
			};

			if (!$scope->isInFirstLevelStatement()) {
				$errors[] = $addTipRight(RuleErrorBuilder::message(sprintf(
					'Right side of %s is always %s.',
					$nodeText,
					$rightType->getValue() ? 'true' : 'false',
				)))->line($originalNode->right->getLine())->build();
			}
		}

		if (count($errors) === 0) {
			$nodeType = $this->treatPhpDocTypesAsCertain ? $scope->getType($originalNode) : $scope->getNativeType($originalNode);
			if ($nodeType instanceof ConstantBooleanType) {
				$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $originalNode, $tipText): RuleErrorBuilder {
					if (!$this->treatPhpDocTypesAsCertain) {
						return $ruleErrorBuilder;
					}

					$booleanNativeType = $scope->doNotTreatPhpDocTypesAsCertain()->getType($originalNode);
					if ($booleanNativeType instanceof ConstantBooleanType) {
						return $ruleErrorBuilder;
					}

					return $ruleErrorBuilder->tip($tipText);
				};

				if (!$scope->isInFirstLevelStatement()) {
					$errors[] = $addTip(RuleErrorBuilder::message(sprintf(
						'Result of %s is always %s.',
						$nodeText,
						$nodeType->getValue() ? 'true' : 'false',
					)))->build();
				}
			}
		}

		return $errors;
	}

	private function getOperatorDescription(LogicalAnd|BooleanAnd $originalNode): string
	{
		return sprintf(
			'%s %s',
			$originalNode instanceof LogicalAnd ? 'logical' : 'boolean',
			strtoupper($originalNode->getOperatorSigil()),
		);
	}

}
