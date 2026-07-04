<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\Comparison\PossiblyImpureTipHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\Instanceof_>
 */
#[RegisteredRule(level: 4)]
final class ImpossibleInstanceOfRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
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
		return Node\Expr\Instanceof_::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		if ($node->class instanceof Node\Name) {
			$className = $scope->resolveName($node->class);
			$classType = new ObjectType($className);
		} else {
			$classType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->class) : $scope->getNativeType($node->class);
			$allowed = new UnionType([
				new StringType(),
				new ObjectWithoutClassType(),
			]);
			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->class,
				'',
				static fn (Type $type): bool => !$allowed->isSuperTypeOf($type)->yes(),
			);
			if (!$typeResult->getType() instanceof ErrorType && !$allowed->isSuperTypeOf($typeResult->getType())->yes()) {
				return [
					RuleErrorBuilder::message(sprintf(
						'Instanceof between %s and %s results in an error.',
						$scope->getType($node->expr)->describe(VerbosityLevel::typeOnly()),
						$classType->describe(VerbosityLevel::typeOnly()),
					))->identifier('instanceof.invalidExprType')->build(),
				];
			}
		}

		$instanceofType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
		if (!$instanceofType instanceof ConstantBooleanType) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $node);
			return [];
		}

		$exprType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node->expr) : $scope->getNativeType($node->expr);
		$reasons = $classType->isSuperTypeOf($exprType)->getReasons();

		$addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node, $reasons): RuleErrorBuilder {
			if ($reasons !== []) {
				return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder->acceptsReasonsTip($reasons));
			}

			if (!$this->treatPhpDocTypesAsCertain) {
				return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
			}

			$instanceofTypeWithoutPhpDocs = $scope->getNativeType($node);
			if ($instanceofTypeWithoutPhpDocs instanceof ConstantBooleanType) {
				return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
			}

			if (!$this->treatPhpDocTypesAsCertainTip) {
				return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
			}

			$ruleErrorBuilder = $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();

			return $this->possiblyImpureTipHelper->addTip($scope, $node, $ruleErrorBuilder);
		};

		if (!$instanceofType->getValue()) {
			$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
				'Instanceof between %s and %s will always evaluate to false.',
				$exprType->describe(VerbosityLevel::typeOnly()),
				$classType->describe(VerbosityLevel::getRecommendedLevelByType($classType)),
			)));
			$ruleError = $errorBuilder->identifier('instanceof.alwaysFalse')->build();
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

		$errorBuilder = $addTip(RuleErrorBuilder::message(sprintf(
			'Instanceof between %s and %s will always evaluate to true.',
			$exprType->describe(VerbosityLevel::typeOnly()),
			$classType->describe(VerbosityLevel::getRecommendedLevelByType($classType)),
		)));
		if ($isLast === false && !$this->reportAlwaysTrueInLastCondition) {
			$errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
		}

		$errorBuilder->identifier('instanceof.alwaysTrue');

		$ruleError = $errorBuilder->build();
		if ($scope->isInTrait()) {
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $node, true, $ruleError);
			return [];
		}

		return [$ruleError];
	}

}
