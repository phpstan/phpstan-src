<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Type\GenericTypeVariableResolver;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;

class FunctionReturnTypeCheck
{

	/** @var \PHPStan\Rules\RuleLevelHelper */
	private $ruleLevelHelper;

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
	 * @param bool $isGenerator
	 * @return RuleError[]
	 */
	public function checkReturnType(
		Scope $scope,
		Type $returnType,
		?Expr $returnValue,
		string $emptyReturnStatementMessage,
		string $voidMessage,
		string $typeMismatchMessage,
		bool $isGenerator
	): array
	{
		if ($isGenerator) {
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
		$verbosityLevel = $returnType->isCallable()->yes() || count(TypeUtils::getConstantArrays($returnType)) > 0
			? VerbosityLevel::value()
			: VerbosityLevel::typeOnly();
		if ($returnValue === null) {
			if (!$isVoidSuperType->no()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					$emptyReturnStatementMessage,
					$returnType->describe($verbosityLevel)
				))->build(),
			];
		}

		$returnValueType = $scope->getType($returnValue);

		if ($isVoidSuperType->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					$voidMessage,
					$returnValueType->describe($verbosityLevel)
				))->build(),
			];
		}

		if (!$this->ruleLevelHelper->accepts($returnType, $returnValueType, $scope->isDeclareStrictTypes())) {
			return [
				RuleErrorBuilder::message(sprintf(
					$typeMismatchMessage,
					$returnType->describe($verbosityLevel),
					$returnValueType->describe($verbosityLevel)
				))->build(),
			];
		}

		return [];
	}

}
