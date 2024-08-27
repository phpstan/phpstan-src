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
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node\Expr\YieldFrom>
 */
final class YieldFromTypeRule implements Rule
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
				))
					->line($node->expr->getStartLine())
					->identifier('generator.nonIterable')
					->build(),
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
				))
					->line($node->expr->getStartLine())
					->identifier('generator.nonIterable')
					->build(),
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
		$acceptsKey = $this->ruleLevelHelper->acceptsWithReason($returnType->getIterableKeyType(), $exprType->getIterableKeyType(), $scope->isDeclareStrictTypes());
		if (!$acceptsKey->result) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableKeyType(), $exprType->getIterableKeyType());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects key type %s, %s given.',
				$returnType->getIterableKeyType()->describe($verbosityLevel),
				$exprType->getIterableKeyType()->describe($verbosityLevel),
			))
				->line($node->expr->getStartLine())
				->identifier('generator.keyType')
				->acceptsReasonsTip($acceptsKey->reasons)
				->build();
		}

		$acceptsValue = $this->ruleLevelHelper->acceptsWithReason($returnType->getIterableValueType(), $exprType->getIterableValueType(), $scope->isDeclareStrictTypes());
		if (!$acceptsValue->result) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType->getIterableValueType(), $exprType->getIterableValueType());
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects value type %s, %s given.',
				$returnType->getIterableValueType()->describe($verbosityLevel),
				$exprType->getIterableValueType()->describe($verbosityLevel),
			))
				->line($node->expr->getStartLine())
				->identifier('generator.valueType')
				->acceptsReasonsTip($acceptsValue->reasons)
				->build();
		}

		$scopeFunction = $scope->getFunction();
		if ($scopeFunction === null) {
			return $messages;
		}

		$currentReturnType = ParametersAcceptorSelector::selectSingle($scopeFunction->getVariants())->getReturnType();
		$exprSendType = $exprType->getTemplateType(Generator::class, 'TSend');
		$thisSendType = $currentReturnType->getTemplateType(Generator::class, 'TSend');
		if ($exprSendType instanceof ErrorType || $thisSendType instanceof ErrorType) {
			return $messages;
		}

		$isSuperType = $exprSendType->isSuperTypeOf($thisSendType);
		if ($isSuperType->no()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects delegated TSend type %s, %s given.',
				$exprSendType->describe(VerbosityLevel::typeOnly()),
				$thisSendType->describe(VerbosityLevel::typeOnly()),
			))->identifier('generator.sendType')->build();
		} elseif ($this->reportMaybes && !$isSuperType->yes()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Generator expects delegated TSend type %s, %s given.',
				$exprSendType->describe(VerbosityLevel::typeOnly()),
				$thisSendType->describe(VerbosityLevel::typeOnly()),
			))->identifier('generator.sendType')->build();
		}

		if (!$scope->isInFirstLevelStatement() && $scope->getType($node)->isVoid()->yes()) {
			$messages[] = RuleErrorBuilder::message('Result of yield from (void) is used.')
				->identifier('generator.void')
				->build();
		}

		return $messages;
	}

}
