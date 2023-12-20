<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\BooleanAndNode;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function count;
use function sprintf;

/**
 * @implements Rule<BooleanAndNode>
 */
class BooleanAndConstantConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		private bool $treatPhpDocTypesAsCertain,
		private bool $bleedingEdge,
		private bool $reportAlwaysTrueInLastCondition,
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
		$nodeText = $this->bleedingEdge ? $originalNode->getOperatorSigil() : '&&';
		$leftType = $this->helper->getBooleanType($scope, $originalNode->left);
		$identifierType = $originalNode instanceof Node\Expr\BinaryOp\BooleanAnd ? 'booleanAnd' : 'logicalAnd';
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

			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if (!$leftType->getValue() || $isLast !== true || $this->reportAlwaysTrueInLastCondition) {
				$errorBuilder = $addTipLeft(RuleErrorBuilder::message(sprintf(
					'Left side of %s is always %s.',
					$nodeText,
					$leftType->getValue() ? 'true' : 'false',
				)))
					->identifier(sprintf('%s.leftAlways%s', $identifierType, $leftType->getValue() ? 'True' : 'False'))
					->line($originalNode->left->getStartLine());
				if ($leftType->getValue() && $isLast === false && !$this->reportAlwaysTrueInLastCondition) {
					$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
				}
				$errors[] = $errorBuilder->build();
			}
		}

		$rightScope = $node->getRightScope();
		$rightType = $this->helper->getBooleanType(
			$rightScope,
			$originalNode->right,
		);
		if ($rightType instanceof ConstantBooleanType && !$scope->isInFirstLevelStatement()) {
			$addTipRight = function (RuleErrorBuilder $ruleErrorBuilder) use ($rightScope, $originalNode, $tipText): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType(
					$rightScope,
					$originalNode->right,
				);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->tip($tipText);
			};

			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if (!$rightType->getValue() || $isLast !== true || $this->reportAlwaysTrueInLastCondition) {
				$errorBuilder = $addTipRight(RuleErrorBuilder::message(sprintf(
					'Right side of %s is always %s.',
					$nodeText,
					$rightType->getValue() ? 'true' : 'false',
				)))
					->identifier(sprintf('%s.rightAlways%s', $identifierType, $rightType->getValue() ? 'True' : 'False'))
					->line($originalNode->right->getStartLine());
				if ($rightType->getValue() && $isLast === false && !$this->reportAlwaysTrueInLastCondition) {
					$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
				}
				$errors[] = $errorBuilder->build();
			}
		}

		if (count($errors) === 0 && !$scope->isInFirstLevelStatement()) {
			$nodeType = $this->treatPhpDocTypesAsCertain ? $scope->getType($originalNode) : $scope->getNativeType($originalNode);
			if ($nodeType instanceof ConstantBooleanType) {
				$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $originalNode, $tipText): RuleErrorBuilder {
					if (!$this->treatPhpDocTypesAsCertain) {
						return $ruleErrorBuilder;
					}

					$booleanNativeType = $scope->getNativeType($originalNode);
					if ($booleanNativeType instanceof ConstantBooleanType) {
						return $ruleErrorBuilder;
					}

					return $ruleErrorBuilder->tip($tipText);
				};

				$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
				if (!$nodeType->getValue() || $isLast !== true || $this->reportAlwaysTrueInLastCondition) {
					$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
						'Result of %s is always %s.',
						$nodeText,
						$nodeType->getValue() ? 'true' : 'false',
					)));
					if ($nodeType->getValue() && $isLast === false && !$this->reportAlwaysTrueInLastCondition) {
						$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
					}

					$errorBuilder->identifier(sprintf('%s.always%s', $identifierType, $nodeType->getValue() ? 'True' : 'False'));

					$errors[] = $errorBuilder->build();
				}
			}
		}

		return $errors;
	}

}
