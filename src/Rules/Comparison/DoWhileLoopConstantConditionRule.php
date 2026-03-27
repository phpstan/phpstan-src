<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\DoWhileLoopConditionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;

/**
 * @implements Rule<DoWhileLoopConditionNode>
 */
#[RegisteredRule(level: 4)]
final class DoWhileLoopConstantConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $helper,
		private PossiblyImpureTipHelper $possiblyImpureTipHelper,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
		#[AutowiredParameter(ref: '%tips.treatPhpDocTypesAsCertain%')]
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return DoWhileLoopConditionNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$exprType = $this->helper->getBooleanType($scope, $node->getCond());
		if ($exprType instanceof ConstantBooleanType) {
			if ($exprType->getValue()) {
				if ($node->hasYield()) {
					$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->getCond());
					return [];
				}
				foreach ($node->getExitPoints() as $exitPoint) {
					$statement = $exitPoint->getStatement();
					if (!$statement instanceof Continue_) {
						$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->getCond());
						return [];
					}
					if (!$statement->num instanceof Int_) {
						continue;
					}
					if ($statement->num->value > 1) {
						$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->getCond());
						return [];
					}
				}
			} else {
				foreach ($node->getExitPoints() as $exitPoint) {
					$statement = $exitPoint->getStatement();
					if ($statement instanceof Break_) {
						$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->getCond());
						return [];
					}
				}
			}

			$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
				if (!$this->treatPhpDocTypesAsCertain) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->getCond(), $ruleErrorBuilder);
				}

				$booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->getCond());
				if ($booleanNativeType instanceof ConstantBooleanType) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->getCond(), $ruleErrorBuilder);
				}
				if (!$this->treatPhpDocTypesAsCertainTip) {
					return $this->possiblyImpureTipHelper->addTip($scope, $node->getCond(), $ruleErrorBuilder);
				}

				$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

				return $this->possiblyImpureTipHelper->addTip($scope, $node->getCond(), $ruleErrorBuilder);
			};

			$ruleError = $addTip(RuleErrorBuilder::message(sprintf(
				'Do-while loop condition is always %s.',
				$exprType->getValue() ? 'true' : 'false',
			)))
				->line($node->getCond()->getStartLine())
				->identifier(sprintf('doWhile.always%s', $exprType->getValue() ? 'True' : 'False'))
				->build();
			if ($scope->isInTrait()) {
				$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node->getCond(), $exprType->getValue(), $ruleError);
				return [];
			}

			return [$ruleError];
		}

		$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node->getCond());
		return [];
	}

}
