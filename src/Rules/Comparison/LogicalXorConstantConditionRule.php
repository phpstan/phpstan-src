<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\LogicalXor;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;

/**
 * @implements Rule<LogicalXor>
 */
#[RegisteredRule(level: 4)]
final class LogicalXorConstantConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
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
		return LogicalXor::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		$leftType = $this->helper->getBooleanType($scope, $node->left);
		if ($leftType instanceof ConstantBooleanType) {
			$addTipLeft = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->left);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
			};

			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if (!$leftType->getValue() || $isLast !== true || $this->reportAlwaysTrueInLastCondition) {
				$errorBuilder = $addTipLeft(RuleErrorBuilder::message(sprintf(
					'Left side of xor is always %s.',
					$leftType->getValue() ? 'true' : 'false',
				)))
					->identifier(sprintf('logicalXor.leftAlways%s', $leftType->getValue() ? 'True' : 'False'))
					->line($node->left->getStartLine());
				if ($leftType->getValue() && $isLast === false && !$this->reportAlwaysTrueInLastCondition) {
					$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
				}
				$errors[] = $errorBuilder->build();
			}
		}

		$rightType = $this->helper->getBooleanType($scope, $node->right);
		if ($rightType instanceof ConstantBooleanType) {
			$addTipRight = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType(
					$scope,
					$node->right,
				);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
			};

			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if (!$rightType->getValue() || $isLast !== true || $this->reportAlwaysTrueInLastCondition) {
				$errorBuilder = $addTipRight(RuleErrorBuilder::message(sprintf(
					'Right side of xor is always %s.',
					$rightType->getValue() ? 'true' : 'false',
				)))
					->identifier(sprintf('logicalXor.rightAlways%s', $rightType->getValue() ? 'True' : 'False'))
					->line($node->right->getStartLine());
				if ($rightType->getValue() && $isLast === false && !$this->reportAlwaysTrueInLastCondition) {
					$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
				}
				$errors[] = $errorBuilder->build();
			}
		}

		return $errors;
	}

}
