<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * Compares what a by-ref parameter is left holding against the type its callers are promised.
 *
 * The promise is either an explicit `@param-out` or, in its absence, the parameter's own type.
 * Which one it is only shows in the error message, so callers report it via $isParamOutType.
 *
 * @internal
 */
#[AutowiredService]
final class ParameterOutTypeCheck
{

	public function __construct(
		private RuleLevelHelper $ruleLevelHelper,
	)
	{
	}

	/**
	 * @param Type $outType Already passed through TypeUtils::resolveLateResolvableTypes()
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		Scope $scope,
		FunctionReflection|ExtendedMethodReflection $inFunction,
		ParameterReflection $parameter,
		Expr $checkedExpr,
		Type $outType,
		bool $isParamOutType,
	): array
	{
		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$checkedExpr,
			'',
			static fn (Type $type): bool => $outType->isSuperTypeOf($type)->yes(),
		);
		if ($typeResult->getType() instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}

		$assignedExprType = $scope->getType($checkedExpr);
		if ($outType->isSuperTypeOf($assignedExprType)->yes()) {
			return [];
		}

		if ($inFunction instanceof ExtendedMethodReflection) {
			$functionDescription = sprintf('method %s::%s()', $inFunction->getDeclaringClass()->getDisplayName(), $inFunction->getName());
		} else {
			$functionDescription = sprintf('function %s()', $inFunction->getName());
		}

		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($outType, $assignedExprType);
		$errorBuilder = RuleErrorBuilder::message(sprintf(
			'Parameter &$%s %s of %s expects %s, %s given.',
			$parameter->getName(),
			$isParamOutType ? '@param-out type' : 'by-ref type',
			$functionDescription,
			$outType->describe($verbosityLevel),
			$assignedExprType->describe($verbosityLevel),
		))->identifier(sprintf('%s.type', $isParamOutType ? 'paramOut' : 'parameterByRef'));

		if (!$isParamOutType) {
			$errorBuilder->tip('You can change the parameter out type with @param-out PHPDoc tag.');
		}

		return [
			$errorBuilder->build(),
		];
	}

}
