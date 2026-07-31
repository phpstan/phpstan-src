<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\CollectedDataEmitter;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\MatchExpressionNode;
use PHPStan\Parser\TryCatchTypeVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use UnhandledMatchError;
use function array_map;
use function count;
use function sprintf;

/**
 * @implements Rule<MatchExpressionNode>
 */
#[RegisteredRule(level: 4)]
final class MatchExpressionRule implements Rule
{

	public function __construct(
		private ConstantConditionRuleHelper $constantConditionRuleHelper,
		private PossiblyImpureTipHelper $possiblyImpureTipHelper,
		private ConstantConditionInTraitHelper $constantConditionInTraitHelper,
		private FunctionCallConstantConditionHelper $functionCallConstantConditionHelper,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return MatchExpressionNode::class;
	}

	public function processNode(Node $node, Scope&NodeCallbackInvoker&CollectedDataEmitter $scope): array
	{
		$matchCondition = $node->getCondition();
		$matchConditionType = $scope->getType($matchCondition);
		$nextArmIsDeadForType = false;
		$nextArmIsDeadForNativeType = false;
		$errors = [];
		$armsCount = count($node->getArms());
		$hasDefault = false;
		foreach ($node->getArms() as $i => $arm) {
			if (
				$nextArmIsDeadForNativeType
				|| ($nextArmIsDeadForType && $this->treatPhpDocTypesAsCertain)
			) {
				continue;
			}
			$armConditions = $arm->getConditions();
			if (count($armConditions) === 0) {
				$hasDefault = true;
			}
			foreach ($armConditions as $armCondition) {
				$armConditionScope = $armCondition->getScope();
				$rawCondition = $armCondition->getCondition();
				// Only for a match(true)-style subject is the arm comparison the
				// same fact as the call's own constant truthiness - the site the
				// ImpossibleCheckType* rules own. For any other subject the
				// comparison ("int is never true") is an independent finding and
				// must not be deduplicated away against their markers.
				$isTypeCheckCandidate = ($matchConditionType->isTrue()->yes() || $matchConditionType->isFalse()->yes())
					&& $this->functionCallConstantConditionHelper->isTypeCheckCandidate($rawCondition);
				$armConditionExpr = new Node\Expr\BinaryOp\Identical(
					$matchCondition,
					$rawCondition,
				);

				$armConditionResult = $armConditionScope->getType($armConditionExpr);
				if (!$armConditionResult instanceof ConstantBooleanType) {
					$this->emitNoError($scope, $armConditionExpr, $rawCondition, $isTypeCheckCandidate);
					continue;
				}
				if ($armConditionResult->getValue()) {
					$nextArmIsDeadForType = true;
				}

				if (!$this->treatPhpDocTypesAsCertain) {
					$armConditionNativeResult = $armConditionScope->getNativeType($armConditionExpr);
					if (!$armConditionNativeResult instanceof ConstantBooleanType) {
						$this->emitNoError($scope, $armConditionExpr, $rawCondition, $isTypeCheckCandidate);
						continue;
					}
					if ($armConditionNativeResult->getValue()) {
						$nextArmIsDeadForNativeType = true;
					}
				}

				if ($matchConditionType instanceof ConstantBooleanType) {
					$armConditionStandaloneResult = $this->constantConditionRuleHelper->getBooleanType($armConditionScope, $rawCondition);
					if (!$armConditionStandaloneResult instanceof ConstantBooleanType) {
						$this->emitNoError($scope, $armConditionExpr, $rawCondition, $isTypeCheckCandidate);
						continue;
					}
				}

				$armLine = $armCondition->getLine();
				if (!$armConditionResult->getValue()) {
					$errorBuilder = RuleErrorBuilder::message(sprintf(
						'Match arm comparison between %s and %s is always false.',
						$armConditionScope->getType($matchCondition)->describe(VerbosityLevel::value()),
						$armConditionScope->getType($rawCondition)->describe(VerbosityLevel::value()),
					))->line($armLine)->identifier('match.alwaysFalse');
					$this->possiblyImpureTipHelper->addTip($armConditionScope, $armConditionExpr, $errorBuilder);
					$ruleError = $errorBuilder->build();
					if ($isTypeCheckCandidate) {
						// the constant-ness of a type-check call is owned by the
						// ImpossibleCheckType* rules; defer and deduplicate against them
						$this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $rawCondition, false, $ruleError);
					} elseif ($scope->isInTrait()) {
						$this->constantConditionInTraitHelper->emitError(self::class, $scope, $armConditionExpr, false, $ruleError);
					} else {
						$errors[] = $ruleError;
					}
					continue;
				}

				if ($i === $armsCount - 1) {
					$this->emitNoError($scope, $armConditionExpr, $rawCondition, $isTypeCheckCandidate);
					continue;
				}

				$message = sprintf(
					'Match arm comparison between %s and %s is always true.',
					$armConditionScope->getType($matchCondition)->describe(VerbosityLevel::value()),
					$armConditionScope->getType($rawCondition)->describe(VerbosityLevel::value()),
				);
				$errorBuilder = RuleErrorBuilder::message($message)
					->line($armLine)
					->identifier('match.alwaysTrue')
					->tip('Remove remaining cases below this one and this error will disappear too.');
				$this->possiblyImpureTipHelper->addTip($armConditionScope, $armConditionExpr, $errorBuilder);
				$ruleError = $errorBuilder->build();
				if ($isTypeCheckCandidate) {
					$this->functionCallConstantConditionHelper->emitFunctionCallError(self::class, $scope, $rawCondition, true, $ruleError);
				} elseif ($scope->isInTrait()) {
					$this->constantConditionInTraitHelper->emitError(self::class, $scope, $armConditionExpr, true, $ruleError);
				} else {
					$errors[] = $ruleError;
				}
			}
		}

		if (!$hasDefault && !$nextArmIsDeadForType) {
			$remainingType = $node->getEndScope()->getType($matchCondition);
			$cases = $remainingType->getEnumCases();
			$casesCount = count($cases);
			if ($casesCount > 1) {
				$remainingType = new UnionType($cases);
			}
			if ($casesCount === 1) {
				$remainingType = $cases[0];
			}
			if (
				!$remainingType instanceof NeverType
				&& !$this->isUnhandledMatchErrorCaught($node)
				&& !$this->hasUnhandledMatchErrorThrowsTag($scope)
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Match expression does not handle remaining %s: %s',
					$remainingType instanceof UnionType ? 'values' : 'value',
					$remainingType->describe(VerbosityLevel::value()),
				))->identifier('match.unhandled')->build();
			}
		}

		return $errors;
	}

	private function emitNoError(
		Scope&NodeCallbackInvoker&CollectedDataEmitter $scope,
		Expr $armConditionExpr,
		Expr $rawCondition,
		bool $isTypeCheckCandidate,
	): void
	{
		if ($isTypeCheckCandidate) {
			$this->functionCallConstantConditionHelper->emitFunctionCallNoError(self::class, $scope, $rawCondition);
		} else {
			$this->constantConditionInTraitHelper->emitNoError(self::class, $scope, $armConditionExpr);
		}
	}

	private function isUnhandledMatchErrorCaught(Node $node): bool
	{
		$tryCatchTypes = $node->getAttribute(TryCatchTypeVisitor::ATTRIBUTE_NAME);
		if ($tryCatchTypes === null) {
			return false;
		}

		$tryCatchType = TypeCombinator::union(...array_map(static fn (string $class) => new ObjectType($class), $tryCatchTypes));

		return $tryCatchType->isSuperTypeOf(new ObjectType(UnhandledMatchError::class))->yes();
	}

	private function hasUnhandledMatchErrorThrowsTag(Scope $scope): bool
	{
		$function = $scope->getFunction();
		if ($function === null) {
			return false;
		}

		$throwsType = $function->getThrowType();
		if ($throwsType === null) {
			return false;
		}

		return $throwsType->isSuperTypeOf(new ObjectType(UnhandledMatchError::class))->yes();
	}

}
