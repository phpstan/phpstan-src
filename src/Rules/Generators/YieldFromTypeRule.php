<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generators;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr\YieldFrom;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\GenericTypeVariableResolver;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\YieldFrom>
 */
class YieldFromTypeRule implements Rule
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
		private bool $reportMaybes,
	)
	{
	}

	public function getNodeType(): string
	{
		return YieldFrom::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$exprType = $scope->getType($node->expr);
		$isIterable = $exprType->isIterable();
		$messagePattern = 'Argument of an invalid type %s passed to yield from, only iterables are supported.';
		if ($isIterable->no()) {
			return [
				RuleErrorBuilder::message(sprintf(
					$messagePattern,
					$exprType->describe(VerbosityLevel::typeOnly()),
				))->line($node->expr->getLine())->build(),
			];
		} elseif (
			!$exprType instanceof MixedType
			&& $this->reportMaybes
			&& $isIterable->maybe()
		) {
			return [
				RuleErrorBuilder::message(sprintf(
					$messagePattern,
					$exprType->describe(VerbosityLevel::typeOnly()),
				))->line($node->expr->getLine())->build(),
			];
		}

		$anonymousFunctionReturnType = $scope->getAnonymousFunctionReturnType();
		$scopeFunction = $scope->getFunction();
		if ($anonymousFunctionReturnType !== null) {
			$returnType = $anonymousFunctionReturnType;
		} elseif ($scopeFunction !== null) {
			$returnType = ParametersAcceptorSelector::selectSingle($scopeFunction->getVariants())->getReturnType();
		} else {
			return []; // already reported by YieldInGeneratorRule
		}

		if ($returnType instanceof MixedType) {
			return [];
		}

		$messages = [];
		if (!$this->ruleLevelHelper->accepts($returnType->getIterableKeyType(), $exprType->getIterableKeyType(), $scope->isDeclareStrictTypes())) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableKeyType(), $exprType->getIterableKeyType());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects key type %s, %s given.',
				$returnType->getIterableKeyType()->describe($verbosityLevel),
				$exprType->getIterableKeyType()->describe($verbosityLevel),
			))->line($node->expr->getLine())->build();
		}
		if (!$this->ruleLevelHelper->accepts($returnType->getIterableValueType(), $exprType->getIterableValueType(), $scope->isDeclareStrictTypes())) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableValueType(), $exprType->getIterableValueType());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects value type %s, %s given.',
				$returnType->getIterableValueType()->describe($verbosityLevel),
				$exprType->getIterableValueType()->describe($verbosityLevel),
			))->line($node->expr->getLine())->build();
		}

		$scopeFunction = $scope->getFunction();
		if ($scopeFunction === null) {
			return $messages;
		}

		if (!$exprType instanceof TypeWithClassName) {
			return $messages;
		}

		$currentReturnType = ParametersAcceptorSelector::selectSingle($scopeFunction->getVariants())->getReturnType();
		if (!$currentReturnType instanceof TypeWithClassName) {
			return $messages;
		}

		$exprSendType = GenericTypeVariableResolver::getType($exprType, Generator::class, 'TSend');
		$thisSendType = GenericTypeVariableResolver::getType($currentReturnType, Generator::class, 'TSend');
		if ($exprSendType === null || $thisSendType === null) {
			return $messages;
		}

		$isSuperType = $exprSendType->isSuperTypeOf($thisSendType);
		if ($isSuperType->no()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects delegated TSend type %s, %s given.',
				$exprSendType->describe(VerbosityLevel::typeOnly()),
				$thisSendType->describe(VerbosityLevel::typeOnly()),
			))->build();
		} elseif ($this->reportMaybes && !$isSuperType->yes()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects delegated TSend type %s, %s given.',
				$exprSendType->describe(VerbosityLevel::typeOnly()),
				$thisSendType->describe(VerbosityLevel::typeOnly()),
			))->build();
		}

		if ($scope->getType($node)->isVoid()->yes() && !$scope->isInFirstLevelStatement()) {
			$messages[] = RuleErrorBuilder::message('Result of yield from (void) is used.')->build();
		}

		return $messages;
	}

}
