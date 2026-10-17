<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Stmt\While_;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Constant\ConstantBooleanType;

/**
 * @implements Rule<While_>
 */
#[RegisteredRule(level: 4)]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/WhileLoopAlwaysFalseConditionRule.cpp')]
final class WhileLoopAlwaysFalseConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
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
		return While_::class;
	}

	public function processNode(
		Node $node,
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
	): array
	{
		$exprType = $this->helper->getBooleanType($scope, $node->cond);
		if ($exprType->isFalse()->yes()) {
			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $ruleErrorBuilder;
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->cond);
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $ruleErrorBuilder;
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $ruleErrorBuilder;
				}

				return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
			};

			$ruleError = $addTip(RuleErrorBuilder::message('While loop condition is always false.'))->line($node->cond->getStartLine())
				->identifier('while.alwaysFalse')
				->build();
			if ($this->functionCallConstantConditionHelper->isTypeCheckCandidate($node->cond)) {
				$this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $node->cond, false, $ruleError);
				return [];
			}
			if ($scope->isInTrait()) {
				$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node->cond, false, $ruleError);
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
