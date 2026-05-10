<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
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
		?Type $nativeReturnType = null,
	): array
	{
		$returnType = TypeUtils::resolveLateResolvableTypes($returnType);

		if ($returnType instanceof NeverType && $returnType->isExplicit()) {
			$builder = RuleErrorBuilder::message($neverMessage)
				->line($returnNode->getStartLine())
				->identifier('return.never');
			if ($nativeReturnType instanceof NeverType && $nativeReturnType->isExplicit()) {
				$builder->nonIgnorable();
			}
			return [$builder->build()];
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

			$builder = RuleErrorBuilder::message(sprintf(
				$emptyReturnStatementMessage,
				$returnType->describe($verbosityLevel),
			))
				->line($returnNode->getStartLine())
				->identifier('return.empty');
			if ($nativeReturnType !== null && $this->isNativeTypeViolated($nativeReturnType, new NullType(), $scope)) {
				$builder->nonIgnorable();
			}
			return [$builder->build()];
		}

		if ($returnNode instanceof Expr\Yield_ || $returnNode instanceof Expr\YieldFrom) {
			return [];
		}

		$returnValueType = $scope->getType($returnValue);
		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, $returnValueType);

		if ($isVoidSuperType->yes()) {
			$builder = RuleErrorBuilder::message(sprintf(
				$voidMessage,
				$returnValueType->describe($verbosityLevel),
			))
				->line($returnNode->getStartLine())
				->identifier('return.void');
			if ($nativeReturnType !== null && $nativeReturnType->isVoid()->yes()) {
				$builder->nonIgnorable();
			}
			return [$builder->build()];
		}

		$accepts = $this->ruleLevelHelper->accepts($returnType, $returnValueType, $scope->isDeclareStrictTypes());
		if (!$accepts->result) {
			$builder = RuleErrorBuilder::message(sprintf(
				$typeMismatchMessage,
				$returnType->describe($verbosityLevel),
				$returnValueType->describe($verbosityLevel),
			))
				->line($returnNode->getStartLine())
				->identifier('return.type')
				->acceptsReasonsTip($accepts->reasons);
			if ($nativeReturnType !== null && $this->isNativeTypeViolated($nativeReturnType, $scope->getNativeType($returnValue), $scope)) {
				$builder->nonIgnorable();
			}
			return [$builder->build()];
		}

		return [];
	}

	private function isNativeTypeViolated(Type $nativeReturnType, Type $nativeValueType, Scope $scope): bool
	{
		$accepts = $nativeReturnType->accepts($nativeValueType, $scope->isDeclareStrictTypes());
		if ($accepts->yes()) {
			return false;
		}

		if (!$scope->isDeclareStrictTypes() && $nativeReturnType->isScalar()->yes() && $nativeValueType->isScalar()->yes()) {
			return false;
		}

		return true;
	}

}
