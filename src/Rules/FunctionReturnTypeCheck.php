<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

#[AutowiredService]
final class FunctionReturnTypeCheck
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
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
					->line($returnNode->getStartLine())
					->identifier('return.never')
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
		if ($returnValue === null) {
			if (!$isVoidSuperType->no()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					$emptyReturnStatementMessage,
					$returnType->describe(VerbosityLevel::getRecommendedLevelByType($returnType, null)),
				))
					->line($returnNode->getStartLine())
					->identifier('return.empty')
					->build(),
			];
		}

		if ($returnNode instanceof Expr\Yield_ || $returnNode instanceof Expr\YieldFrom) {
			return [];
		}

		$returnValueType = $scope->getType($returnValue);

		if ($isVoidSuperType->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					$voidMessage,
					$returnValueType->describe(VerbosityLevel::getRecommendedLevelByType($returnType, $returnValueType)),
				))
					->line($returnNode->getStartLine())
					->identifier('return.void')
					->build(),
			];
		}

		$accepts = $this->ruleLevelHelper->accepts($returnType, $returnValueType, $scope->isDeclareStrictTypes());
		if (!$accepts->result) {
			$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, $returnValueType);

			return [
				RuleErrorBuilder::message(sprintf(
					$typeMismatchMessage,
					$returnType->describe($verbosityLevel),
					$returnValueType->describe($verbosityLevel),
				))
					->line($returnNode->getStartLine())
					->identifier('return.type')
					->acceptsReasonsTip($accepts->reasons)
					->build(),
			];
		}

		return [];
	}

}
