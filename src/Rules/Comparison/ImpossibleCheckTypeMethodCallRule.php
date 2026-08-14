<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MethodCallExpressionNode;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<MethodCallExpressionNode>
 */
#[RegisteredRule(level: 4)]
final class ImpossibleCheckTypeMethodCallRule implements Rule
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
		return MethodCallExpressionNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$methodCall = $node->getOriginalNode();
		$nodeResult = $node->getResult();
		if (!$methodCall->name instanceof Node\Identifier) {
			return [];
		}
		$methodName = $methodCall->name->name;

		$reasons = [];
		$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $methodCall, $nodeResult, null, $reasons);
		if ($isAlways === null) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $methodCall);
			return [];
		}

		$this->functionCallConstantConditionHelper->emitImpossibleCheckReported($scope, $methodCall);

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $methodCall, $nodeResult, $reasons): RuleErrorBuilder {
			if ($reasons !== []) {
				return $this->possiblyImpureTipHelper->addTip($scope, $methodCall, $ruleErrorBuilder->acceptsReasonsTip($reasons));
			}

			if (!$this->treatPhpDocTypesAsCertain) {
				return $this->possiblyImpureTipHelper->addTip($scope, $methodCall, $ruleErrorBuilder);
			}

			$isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $methodCall, $nodeResult, null);
			if ($isAlways !== null) {
				return $this->possiblyImpureTipHelper->addTip($scope, $methodCall, $ruleErrorBuilder);
			}
			if (!$this->treatPhpDocTypesAsCertainTip) {
				return $this->possiblyImpureTipHelper->addTip($scope, $methodCall, $ruleErrorBuilder);
			}

			$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

			return $this->possiblyImpureTipHelper->addTip($scope, $methodCall, $ruleErrorBuilder);
		};

		if (!$isAlways) {
			$method = $this->getMethod($methodCall->var, $methodName, $scope);
			$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
				'Call to method %s::%s()%s will always evaluate to false.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $methodCall->getArgs()),
			)));
			$ruleError = $errorBuilder->identifier('method.impossibleType')->build();
			if ($scope->isInTrait()) {
				$this->constantConditionInTraitHelper->emitError(self::class, $scope, $methodCall, false, $ruleError);
				return [];
			}

			return [$ruleError];
		}

		$isLast = $methodCall->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
		if ($isLast === true && !$this->reportAlwaysTrueInLastCondition) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $methodCall);
			return [];
		}

		$method = $this->getMethod($methodCall->var, $methodName, $scope);
		$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
			'Call to method %s::%s()%s will always evaluate to true.',
			$method->getDeclaringClass()->getDisplayName(),
			$method->getName(),
			$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $methodCall->getArgs()),
		)));
		if ($isLast === false && !$this->reportAlwaysTrueInLastCondition) {
			$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
		}

		$errorBuilder->identifier('method.alreadyNarrowedType');

		$ruleError = $errorBuilder->build();
		if ($scope->isInTrait()) {
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $methodCall, true, $ruleError);
			return [];
		}

		return [$ruleError];
	}

	private function getMethod(
		Expr $var,
		string $methodName,
		Scope $scope,
	): MethodReflection
	{
		$calledOnType = $scope->getType($var);
		$method = $scope->getMethodReflection($calledOnType, $methodName);
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		return $method;
	}

}
