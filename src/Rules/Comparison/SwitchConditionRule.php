<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\SwitchConditionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<SwitchConditionNode>
 */
final class SwitchConditionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $constantConditionRuleHelper,
		private PossiblyImpureTipHelper $possiblyImpureTipHelper,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return SwitchConditionNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$subject = $node->getSubject();
		$caseCondition = $node->getCaseCondition();
		$conditionExpr = new Equal($subject, $caseCondition);

		$conditionType = $scope->getType($conditionExpr);
		if (!$this->isConstantBoolean($conditionType)) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
			return [];
		}

		if (!$this->treatPhpDocTypesAsCertain) {
			$conditionNativeType = $scope->getNativeType($conditionExpr);
			if (!$this->isConstantBoolean($conditionNativeType)) {
				$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
				return [];
			}
		}

		$subjectType = $scope->getType($subject);
		if ($this->isConstantBoolean($subjectType)) {
			$caseConditionStandaloneType = $this->constantConditionRuleHelper->getBooleanType($scope, $caseCondition);
			if (!$this->isConstantBoolean($caseConditionStandaloneType)) {
				$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
				return [];
			}
		}

		if (!$conditionType->isFalse()->yes()) {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message(sprintf(
			'Switch condition comparison between %s and %s is always false.',
			$subjectType->describe(VerbosityLevel::value()),
			$scope->getType($caseCondition)->describe(VerbosityLevel::value()),
		))->line($caseCondition->getStartLine())->identifier('switch.alwaysFalse');
		$this->possiblyImpureTipHelper->addTip($scope, $conditionExpr, $errorBuilder);
		$ruleError = $errorBuilder->build();

		if ($scope->isInTrait()) {
			$this->constantConditionInTraitHelper->emitError(self::class, $scope, $conditionExpr, false, $ruleError);
			return [];
		}

		return [$ruleError];
	}

	private function isConstantBoolean(Type $type): bool
	{
		return $type->isTrue()->yes() || $type->isFalse()->yes();
	}

}
