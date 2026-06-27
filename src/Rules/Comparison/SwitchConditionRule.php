<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\SwitchConditionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
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
		private ExprPrinter $exprPrinter,
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
		$errors = [];
		$nextCaseIsDeadForType = false;
		$nextCaseIsDeadForNativeType = false;
		$seenCases = [];

		foreach ($node->getArms() as $arm) {
			if (
				$nextCaseIsDeadForNativeType
				|| ($nextCaseIsDeadForType && $this->treatPhpDocTypesAsCertain)
			) {
				continue;
			}

			$armScope = $arm->getScope();
			$caseCondition = $arm->getCaseCondition();

			$caseKey = $this->getCaseKey($armScope->getType($caseCondition));
			if ($caseKey !== null) {
				$firstSeen = null;
				foreach ($seenCases as $seenCase) {
					if ($seenCase['key'] === $caseKey) {
						$firstSeen = $seenCase;
						break;
					}
				}

				if ($firstSeen !== null) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Case %s in switch is a duplicate of case %s on line %d.',
						$this->exprPrinter->printExpr($caseCondition),
						$firstSeen['printed'],
						$firstSeen['line'],
					))->line($arm->getLine())->identifier('switch.duplicateCase')->build();
					continue;
				}

				$seenCases[] = [
					'key' => $caseKey,
					'printed' => $this->exprPrinter->printExpr($caseCondition),
					'line' => $arm->getLine(),
				];
			}

			$conditionExpr = new Equal($subject, $caseCondition);

			$conditionType = $armScope->getType($conditionExpr);
			if (!$this->isConstantBoolean($conditionType)) {
				$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
				continue;
			}
			if ($conditionType->isTrue()->yes()) {
				$nextCaseIsDeadForType = true;
			}

			if (!$this->treatPhpDocTypesAsCertain) {
				$conditionNativeType = $armScope->getNativeType($conditionExpr);
				if (!$this->isConstantBoolean($conditionNativeType)) {
					$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
					continue;
				}
				if ($conditionNativeType->isTrue()->yes()) {
					$nextCaseIsDeadForNativeType = true;
				}
			}

			$subjectType = $armScope->getType($subject);
			if ($this->isConstantBoolean($subjectType)) {
				$caseConditionStandaloneType = $this->constantConditionRuleHelper->getBooleanType($armScope, $caseCondition);
				if (!$this->isConstantBoolean($caseConditionStandaloneType)) {
					$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
					continue;
				}
			}

			if ($conditionType->isFalse()->yes()) {
				$errorBuilder = RuleErrorBuilder::message(sprintf(
					'Switch condition comparison between %s and %s is always false.',
					$subjectType->describe(VerbosityLevel::value()),
					$armScope->getType($caseCondition)->describe(VerbosityLevel::value()),
				))->line($arm->getLine())->identifier('switch.alwaysFalse');
				$this->possiblyImpureTipHelper->addTip($armScope, $conditionExpr, $errorBuilder);
				$ruleError = $errorBuilder->build();
				if ($scope->isInTrait()) {
					$this->constantConditionInTraitHelper->emitError(self::class, $scope, $conditionExpr, false, $ruleError);
				} else {
					$errors[] = $ruleError;
				}
				continue;
			}

			if ($arm->isLast()) {
				$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $conditionExpr);
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'Switch condition comparison between %s and %s is always true.',
				$subjectType->describe(VerbosityLevel::value()),
				$armScope->getType($caseCondition)->describe(VerbosityLevel::value()),
			))->line($arm->getLine())->identifier('switch.alwaysTrue')
				->tip('Remove remaining cases below this one and this error will disappear too.');
			$this->possiblyImpureTipHelper->addTip($armScope, $conditionExpr, $errorBuilder);
			$ruleError = $errorBuilder->build();
			if ($scope->isInTrait()) {
				$this->constantConditionInTraitHelper->emitError(self::class, $scope, $conditionExpr, true, $ruleError);
			} else {
				$errors[] = $ruleError;
			}
		}

		return $errors;
	}

	private function isConstantBoolean(Type $type): bool
	{
		return $type->isTrue()->yes() || $type->isFalse()->yes();
	}

	/**
	 * Builds a comparable key identifying a single constant case value (scalar or
	 * enum case), or null when the case condition does not have one definite value.
	 *
	 * @return array{'scalar', int|float|string|bool|null}|array{'enum', string, string}|null
	 */
	private function getCaseKey(Type $caseConditionType): ?array
	{
		$scalarValues = $caseConditionType->getConstantScalarValues();
		if (count($scalarValues) === 1) {
			return ['scalar', $scalarValues[0]];
		}

		$enumCases = $caseConditionType->getEnumCases();
		if (count($enumCases) === 1) {
			return ['enum', $enumCases[0]->getClassName(), $enumCases[0]->getEnumCaseName()];
		}

		return null;
	}

}
