<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

class FunctionReturnTypeCheck
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	/**
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
		bool $isGenerator,
	): array
	{
		$returnType = TypeUtils::resolveLateResolvableTypes($returnType);

		if ($returnType instanceof NeverType && $returnType->isExplicit()) {
			return [
				RuleErrorBuilder::message($neverMessage)
					->line($returnNode->getLine())
					->build(),
			];
		}

		if ($isGenerator) {
			$returnType = $returnType->getTemplateType(Generator::class, 'TReturn');
			if ($returnType instanceof ErrorType) {
				return [];
			}
		}

		$isVoidSuperType = $returnType->isVoid();
		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, null);
		if ($returnValue === null) {
			if (!$isVoidSuperType->no()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					$emptyReturnStatementMessage,
					$returnType->describe($verbosityLevel),
				))->line($returnNode->getLine())->build(),
			];
		}

		if ($returnNode instanceof Expr\Yield_ || $returnNode instanceof Expr\YieldFrom) {
			return [];
		}

		$returnValueType = $scope->getType($returnValue);
		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, $returnValueType);

		if ($isVoidSuperType->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					$voidMessage,
					$returnValueType->describe($verbosityLevel),
				))->line($returnNode->getLine())->build(),
			];
		}

		$accepts = $this->ruleLevelHelper->acceptsWithReason($returnType, $returnValueType, $scope->isDeclareStrictTypes());
		if (!$accepts->result) {
			return [
				RuleErrorBuilder::message(sprintf(
					$typeMismatchMessage,
					$returnType->describe($verbosityLevel),
					$returnValueType->describe($verbosityLevel),
				))->line($returnNode->getLine())->acceptsReasonsTip($accepts->reasons)->build(),
			];
		}

		return [];
	}

}
