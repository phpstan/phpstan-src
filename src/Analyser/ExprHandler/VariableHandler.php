<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ExprHandler\Helper\IdenticalNarrowingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\IssetabilityDescriptor;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function is_string;

/**
 * @implements ExprHandler<Variable>
 */
#[AutowiredService]
final class VariableHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private IdenticalNarrowingHelper $identicalNarrowingHelper,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Variable;
	}

	/**
	 * Evaluates the variable as a read on the asking scope.
	 *
	 * @return Closure(bool $nativeTypesPromoted): Type
	 */
	private function createTypeCallback(Variable $expr, NodeScopeResolver $nodeScopeResolver, MutatingScope $beforeScope, ?ExpressionResult $nameResult = null, ?ExpressionResult $nameArgResult = null): Closure
	{
		return function (bool $nativeTypesPromoted) use ($expr, $nameResult, $nameArgResult, $nodeScopeResolver, $beforeScope): Type {
			$readScope = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
			if (is_string($expr->name)) {
				if ($readScope->hasVariableType($expr->name)->no()) {
					return new ErrorType();
				}

				return $readScope->getVariableType($expr->name);
			}

			// this branch is only reached when $expr->name is an Expr, which is
			// exactly when the caller (processExpr) set $nameResult
			if ($nameResult === null) {
				throw new ShouldNotHappenException();
			}
			$nameType = $nativeTypesPromoted ? $nameResult->getNativeType() : $nameResult->getType();
			if (count($nameType->getConstantStrings()) > 0) {
				$types = [];
				foreach ($nameType->getConstantStrings() as $constantString) {
					// "name === 'str'" composed from the name expression's walk
					// result - no synthetic Identical walk; the literal side is a
					// result the scalar handler would have produced
					$literalExpr = new String_($constantString->getValue());
					$literalResult = $this->expressionResultFactory->create(
						$readScope,
						beforeScope: $readScope,
						expr: $literalExpr,
						hasYield: false,
						isAlwaysTerminating: false,
						throwPoints: [],
						impurePoints: [],
						typeCallback: static fn (): Type => $constantString,
						specifyTypesCallback: SpecifiedTypes::emptySpecifyCallback(),
					);
					$specifiedTypes = $this->identicalNarrowingHelper->specifyIdentical(
						$nodeScopeResolver,
						$expr->name,
						$literalExpr,
						$nameResult,
						$literalResult,
						TypeSpecifierContext::createTruthy(),
						$readScope,
						$nameArgResult,
						null,
						fn (): Type => $this->initializerExprTypeResolver->resolveIdenticalType($nameType, $constantString)->type,
					);
					$variableScope = $readScope->applySpecifiedTypes($specifiedTypes ?? new SpecifiedTypes());
					if ($variableScope->hasVariableType($constantString->getValue())->no()) {
						$types[] = new ErrorType();
						continue;
					}

					$types[] = $variableScope->getVariableType($constantString->getValue());
				}

				return TypeCombinator::union(...$types);
			}

			return new MixedType();
		};
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		$nameResult = null;
		if (!is_string($expr->name)) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
		}

		return $this->composeResult($nodeScopeResolver, $expr, $nameResult, $storage, $beforeScope);
	}

	/**
	 * Builds the variable read's ExpressionResult from an already-walked state -
	 * no node processing happens here. processExpr() routes through this after
	 * walking a dynamic name; AssignHandler::prepareTarget() calls it to price a
	 * read-modify-write target without re-walking it.
	 */
	public function composeResult(NodeScopeResolver $nodeScopeResolver, Variable $expr, ?ExpressionResult $nameResult, ExpressionResultStorage $storage, MutatingScope $beforeScope): ExpressionResult
	{
		$scope = $beforeScope;
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		if (is_string($expr->name)) {
			if (in_array($expr->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
				$impurePoints[] = new ImpurePoint($scope, $expr, 'superglobal', 'access to superglobal variable', true);
			}
		} elseif ($nameResult !== null) {
			$hasYield = $nameResult->hasYield();
			$throwPoints = $nameResult->getThrowPoints();
			$impurePoints = $nameResult->getImpurePoints();
			$isAlwaysTerminating = $nameResult->isAlwaysTerminating();
			$scope = $nameResult->getScope();
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $hasYield,
			isAlwaysTerminating: $isAlwaysTerminating,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			issetabilityDescriptor: is_string($expr->name) ? IssetabilityDescriptor::variable($expr->name) : null,
			typeCallback: $this->createTypeCallback($expr, $nodeScopeResolver, $beforeScope, $nameResult, is_string($expr->name) ? null : $this->identicalNarrowingHelper->captureFirstArgResult($expr->name, $storage)),
			specifyTypesCallback: fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context),
		);
	}

}
