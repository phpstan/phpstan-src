<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;

/**
 * @implements Rule<Node\Expr\BooleanNot>
 */
#[RegisteredRule(level: 4)]
final class BooleanNotConstantConditionRule implements Rule
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
		return Node\Expr\BooleanNot::class;
	}

	public function processNode(
		Node $node,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
	): array
	{
		$exprType = $this->helper->getBooleanType($scope, $node->expr);
		if ($exprType instanceof ConstantBooleanType) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->expr, $ruleErrorBuilder);
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->expr);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->expr, $ruleErrorBuilder);
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->expr, $ruleErrorBuilder);
				}

				$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

				return $this->possiblyImpureTipHelper->addTip($scope, $node->expr, $ruleErrorBuilder);
			};

			$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
			if ($exprType->getValue() || $isLast !== true || $this->reportAlwaysTrueInLastCondition) {
				$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
					'Negated boolean expression is always %s.',
					$exprType->getValue() ? 'false' : 'true',
				)))->line($node->expr->getStartLine());
				if (!$exprType->getValue() && $isLast === false && !$this->reportAlwaysTrueInLastCondition) {
					$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
				}

				$errorBuilder->identifier(sprintf('booleanNot.always%s', $exprType->getValue() ? 'False' : 'True'));

				$ruleError = $errorBuilder->build();
				if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->expr)) {
					$this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $node->expr, !$exprType->getValue(), $ruleError);
					return [];
				}
				if ($scope->isInTrait()) {
					$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node->expr, !$exprType->getValue(), $ruleError);
					return [];
				}

				return [$ruleError];
			}
		}

		if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->expr)) {
			$this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $node->expr);
		} else {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->expr);
		}
		return [];
	}

}
