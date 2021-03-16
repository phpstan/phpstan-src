<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Type\GenericTypeVariableResolver;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class FunctionReturnTypeCheck
{

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	public function __construct(RuleLevelHelper $ruleLevelHelper)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	/**
	 * @param \PHPStan\Analyser\Scope $scope
	 * @param \PHPStan\Type\Type $returnType
	 * @param \PhpParser\Node\Expr|null $returnValue
	 * @param string $emptyReturnStatementMessage
	 * @param string $voidMessage
	 * @param string $typeMismatchMessage
	 * @param string $returnYieldFromMessage
	 * @param bool $isGenerator
	 * @return RuleError[]
	 */
	public function checkReturnType(
		Scope $scope,
		Type $returnType,
		?Expr $returnValue,
		Node $returnNode,
		string $emptyReturnStatementMessage,
		string $voidMessage,
		string $typeMismatchMessage,
		string $neverMessage,
		string $returnYieldFromMessage,
		bool $isGenerator
	): array
	{
		if ($returnType instanceof NeverType && $returnType->isExplicit()) {
			return [
				RuleErrorBuilder::message($neverMessage)
					->line($returnNode->getLine())
					->build(),
			];
		}

		if ($isGenerator) {
			$returnTypeIsIterable = (new IterableType(new MixedType(), new MixedType()))
				->isSuperTypeOf($returnType)
				->yes();
			$returnIsYieldish = $returnValue === null || $returnValue instanceof Expr\YieldFrom;

			if ($returnTypeIsIterable && !$returnIsYieldish) {
				return [
					RuleErrorBuilder::message($returnYieldFromMessage)
						->line($returnNode->getLine())
						->build(),
				];
			}

			if (!$returnType instanceof TypeWithClassName) {
				return [];
			}

			$returnType = GenericTypeVariableResolver::getType(
				$returnType,
				\Generator::class,
				'TReturn'
			);
			if ($returnType === null) {
				return [];
			}
		}

		$isVoidSuperType = (new VoidType())->isSuperTypeOf($returnType);
		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, null);
		if ($returnValue === null) {
			if (!$isVoidSuperType->no()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					$emptyReturnStatementMessage,
					$returnType->describe($verbosityLevel)
				))->line($returnNode->getLine())->build(),
			];
		}

		$returnValueType = $scope->getType($returnValue);
		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, $returnValueType);

		if ($isVoidSuperType->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					$voidMessage,
					$returnValueType->describe($verbosityLevel)
				))->line($returnNode->getLine())->build(),
			];
		}

		if (!$this->ruleLevelHelper->accepts($returnType, $returnValueType, $scope->isDeclareStrictTypes())) {
			return [
				RuleErrorBuilder::message(sprintf(
					$typeMismatchMessage,
					$returnType->describe($verbosityLevel),
					$returnValueType->describe($verbosityLevel)
				))->line($returnNode->getLine())->build(),
			];
		}

		return [];
	}

}
