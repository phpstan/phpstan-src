<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
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
#[RegisteredRule(level: 4)]
final class BooleanAndConstantConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		private PossiblyImpureTipHelper $possiblyImpureTipHelper,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
		private FunctionCallConstantConditionHelper $functionCallConstantConditionHelper,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter]
		private bool $reportAlwaysTrueInLastCondition,
		#[AutowiredParameter(ref: '%tips.treatPhpDocTypesAsCertain%')]
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return BooleanAndNode::class;
	}

	public function processNode(
		Node $node,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
	): array
	{
		$errors = [];
		$originalNode = $node->getOriginalNode();
		$nodeText = $originalNode->getOperatorSigil();
		$leftType = $this->helper->getBooleanType($scope, $originalNode->left);
		$identifierType = $originalNode instanceof Node\Expr\BinaryOp\BooleanAnd ? 'booleanAnd' : 'logicalAnd';
		$isInTrait = $scope->isInTrait();
		$hasLeftOrRightError = false;
		if ($leftType instanceof ConstantBooleanType) {
			$addTipLeft = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $originalNode): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $this->possiblyImpureTipHelper->addTip($scope, $originalNode->left, $ruleErrorBuilder);
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $originalNode->left);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $this->possiblyImpureTipHelper->addTip($scope, $originalNode->left, $ruleErrorBuilder);
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $this->possiblyImpureTipHelper->addTip($scope, $originalNode->left, $ruleErrorBuilder);
				}

				$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

				return $this->possiblyImpureTipHelper->addTip($scope, $originalNode->left, $ruleErrorBuilder);
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
				$ruleError = $errorBuilder->build();
				$hasLeftOrRightError = true;
				if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($originalNode->left)) {
					$this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $originalNode->left, $leftType->getValue(), $ruleError);
				} elseif ($isInTrait) {
					$this->constantConditionInTraitHelper->emitError(self::class, $scope, $originalNode->left, $leftType->getValue(), $ruleError);
				} else {
					$errors[] = $ruleError;
				}
			} else {
				$this->emitNoError($scope, $originalNode->left);
			}
		} else {
			$this->emitNoError($scope, $originalNode->left);
		}

		$rightScope = $node->getRightScope();
		$rightType = $this->helper->getBooleanType(
			$rightScope,
			$originalNode->right,
		);
		if ($rightType instanceof ConstantBooleanType && !$scope->isInFirstLevelStatement()) {
			$addTipRight = function (RuleErrorBuilder $ruleErrorBuilder) use ($rightScope, $originalNode): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $this->possiblyImpureTipHelper->addTip($rightScope, $originalNode->right, $ruleErrorBuilder);
				}

				$booleanNativeType = $this->helper->getNativeBooleanType(
					$rightScope,
					$originalNode->right,
				);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $this->possiblyImpureTipHelper->addTip($rightScope, $originalNode->right, $ruleErrorBuilder);
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $this->possiblyImpureTipHelper->addTip($rightScope, $originalNode->right, $ruleErrorBuilder);
				}

				$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

				return $this->possiblyImpureTipHelper->addTip($rightScope, $originalNode->right, $ruleErrorBuilder);
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
				$ruleError = $errorBuilder->build();
				$hasLeftOrRightError = true;
				if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($originalNode->right)) {
					$this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $originalNode->right, $rightType->getValue(), $ruleError);
				} elseif ($isInTrait) {
					$this->constantConditionInTraitHelper->emitError(self::class, $scope, $originalNode->right, $rightType->getValue(), $ruleError);
				} else {
					$errors[] = $ruleError;
				}
			} else {
				$this->emitNoError($scope, $originalNode->right);
			}
		} else {
			$this->emitNoError($scope, $originalNode->right);
		}

		if (count($errors) === 0 && !$hasLeftOrRightError && !$scope->isInFirstLevelStatement()) {
			$nodeType = $this->treatPhpDocTypesAsCertain ? $scope->getType($originalNode) : $scope->getNativeType($originalNode);
			if ($nodeType instanceof ConstantBooleanType) {
				$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $originalNode): RuleErrorBuilder {
					if (!$this->treatPhpDocTypesAsCertain) {
						return $this->possiblyImpureTipHelper->addTip($scope, $originalNode, $ruleErrorBuilder);
					}

					$booleanNativeType = $scope->getNativeType($originalNode);
					if ($booleanNativeType instanceof ConstantBooleanType) {
						return $this->possiblyImpureTipHelper->addTip($scope, $originalNode, $ruleErrorBuilder);
					}
					if (!$this->treatPhpDocTypesAsCertainTip) {
						return $this->possiblyImpureTipHelper->addTip($scope, $originalNode, $ruleErrorBuilder);
					}

					$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

					return $this->possiblyImpureTipHelper->addTip($scope, $originalNode, $ruleErrorBuilder);
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

					$ruleError = $errorBuilder->build();
					if ($isInTrait) {
						$this->constantConditionInTraitHelper->emitError(self::class, $scope, $originalNode, $nodeType->getValue(), $ruleError);
					} else {
						$errors[] = $ruleError;
					}
				} else {
					$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $originalNode);
				}
			} else {
				$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $originalNode);
			}
		}

		return $errors;
	}

	private function emitNoError(
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Expr $expr,
	): void
	{
		if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($expr)) {
			$this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $expr);
		} else {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $expr);
		}
	}

}
