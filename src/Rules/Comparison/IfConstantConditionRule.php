<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\If_>
 */
#[RegisteredRule(level: 4)]
final class IfConstantConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		private PossiblyImpureTipHelper $possiblyImpureTipHelper,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
		private FunctionCallConstantConditionHelper $functionCallConstantConditionHelper,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter(ref: '%tips.treatPhpDocTypesAsCertain%')]
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\If_::class;
	}

	public function processNode(
		Node $node,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
	): array
	{
		$exprType = $this->helper->getBooleanType($scope, $node->cond);
		if ($exprType instanceof ConstantBooleanType) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->cond, $ruleErrorBuilder);
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->cond);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->cond, $ruleErrorBuilder);
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->cond, $ruleErrorBuilder);
				}

				$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

				return $this->possiblyImpureTipHelper->addTip($scope, $node->cond, $ruleErrorBuilder);
			};

			$ruleError = $addTip(RuleErrorBuilder::message(sprintf(
				'If condition is always %s.',
				$exprType->getValue() ? 'true' : 'false',
			)))
				->identifier(sprintf('if.always%s', $exprType->getValue() ? 'True' : 'False'))
				->line($node->cond->getStartLine())->build();
			if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->cond)) {
				$this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $node->cond, $exprType->getValue(), $ruleError);
				return [];
			}
			if ($scope->isInTrait()) {
				$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node->cond, $exprType->getValue(), $ruleError);
				return [];
			}

			return [$ruleError];
		}

		if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->cond)) {
			$this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $node->cond);
		} else {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->cond);
		}
		return [];
	}

}
