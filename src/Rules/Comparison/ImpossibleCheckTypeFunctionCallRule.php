<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\FunctionCallExpressionNode;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<FunctionCallExpressionNode>
 */
#[RegisteredRule(level: 4)]
final class ImpossibleCheckTypeFunctionCallRule implements Rule
{

	public function __construct(
		private ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
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
		return FunctionCallExpressionNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$funcCall = $node->getOriginalNode();
		$nodeResult = $node->getResult();
		if (!$funcCall->name instanceof Node\Name) {
			return [];
		}

		$functionName = (string) $funcCall->name;
		$reasons = [];
		$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $funcCall, $nodeResult, null, $reasons);
		if ($isAlways === null) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $funcCall);
			return [];
		}

		$this->functionCallConstantConditionHelper->emitImpossibleCheckReported($scope, $funcCall);

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $funcCall, $nodeResult, $reasons): RuleErrorBuilder {
			if ($reasons !== []) {
				return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder->acceptsReasonsTip($reasons));
			}

			if (!$this->treatPhpDocTypesAsCertain) {
				return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder);
			}

			$isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $funcCall, $nodeResult, null, $reasons);
			if ($isAlways !== null) {
				return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder);
			}
			if (!$this->treatPhpDocTypesAsCertainTip) {
				return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder);
			}

			$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

			return $this->possiblyImpureTipHelper->addTip($scope, $funcCall, $ruleErrorBuilder);
		};

		if (!$isAlways) {
			$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
				'Call to function %s()%s will always evaluate to false.',
				$functionName,
				$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $funcCall->getArgs()),
			)));
			$ruleError = $errorBuilder->identifier('function.impossibleType')->build();
			if ($scope->isInTrait()) {
				$this->constantConditionInTraitHelper->emitError(self::class, $scope, $funcCall, false, $ruleError);
				return [];
			}

			return [$ruleError];
		}

		$isLast = $funcCall->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
		if ($isLast === true && !$this->reportAlwaysTrueInLastCondition) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $funcCall);
			return [];
		}

		$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
			'Call to function %s()%s will always evaluate to true.',
			$functionName,
			$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $funcCall->getArgs()),
		)));
		if ($isLast === false && !$this->reportAlwaysTrueInLastCondition) {
			$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
		}

		$errorBuilder->identifier('function.alreadyNarrowedType');

		$ruleError = $errorBuilder->build();
		if ($scope->isInTrait()) {
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $funcCall, true, $ruleError);
			return [];
		}

		return [$ruleError];
	}

}
