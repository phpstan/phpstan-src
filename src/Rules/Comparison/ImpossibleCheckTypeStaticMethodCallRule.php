<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;

/**
 * @implements Rule<Node\Expr\StaticCall>
 */
#[RegisteredRule(level: 4)]
final class ImpossibleCheckTypeStaticMethodCallRule implements Rule
{

	public function __construct(
		private ImpossibleCheckTypeHelper $impossibleCheckTypeHelper,
		private PossiblyImpureTipHelper $possiblyImpureTipHelper,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
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
		return Node\Expr\StaticCall::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		$reasons = [];
		$isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $node, $reasons);
		if ($isAlways === null) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
			return [];
		}

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
			if (!$this->treatPhpDocTypesAsCertain) {
				return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
			}

			$isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $node);
			if ($isAlways !== null) {
				return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
			}
			if (!$this->treatPhpDocTypesAsCertainTip) {
				return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
			}

			$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

			return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
		};

		if (!$isAlways) {
			$method = $this->getMethod($node->class, $node->name->name, $scope);

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'Call to static method %s::%s()%s will always evaluate to false.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
			));
			if ($reasons !== []) {
				$errorBuilder = $this->possiblyImpureTipHelper->addTip($scope, $node, $errorBuilder->acceptsReasonsTip($reasons));
			} else {
				$errorBuilder = $addTip($errorBuilder);
			}
			$ruleError = $errorBuilder->identifier('staticMethod.impossibleType')->build();
			if ($scope->isInTrait()) {
				$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, false, $ruleError);
				return [];
			}

			return [$ruleError];
		}

		$isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
		if ($isLast === true && !$this->reportAlwaysTrueInLastCondition) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
			return [];
		}

		$method = $this->getMethod($node->class, $node->name->name, $scope);
		$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
			'Call to static method %s::%s()%s will always evaluate to true.',
			$method->getDeclaringClass()->getDisplayName(),
			$method->getName(),
			$this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()),
		)));
		if ($isLast === false && !$this->reportAlwaysTrueInLastCondition) {
			$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
		}

		$errorBuilder->identifier('staticMethod.alreadyNarrowedType');

		$ruleError = $errorBuilder->build();
		if ($scope->isInTrait()) {
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, true, $ruleError);
			return [];
		}

		return [$ruleError];
	}

	/**
	 * @param Node\Name|Expr $class
	 * @throws ShouldNotHappenException
	 */
	private function getMethod(
		$class,
		string $methodName,
		Scope $scope,
	): MethodReflection
	{
		if ($class instanceof Node\Name) {
			$calledOnType = $scope->resolveTypeByName($class);
		} else {
			$calledOnType = $scope->getType($class);
		}

		$method = $scope->getMethodReflection($calledOnType, $methodName);
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		return $method;
	}

}
