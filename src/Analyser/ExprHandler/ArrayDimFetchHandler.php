<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use ArrayAccess;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\NullsafeShortCircuitingHelper;
use PHPStan\Analyser\IssetabilityDescriptor;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<ArrayDimFetch>
 */
#[AutowiredService]
final class ArrayDimFetchHandler implements ExprHandler
{

	public function __construct(private ExpressionResultFactory $expressionResultFactory)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof ArrayDimFetch;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->dim === null) {
			return new NeverType();
		}

		$offsetAccessibleType = $scope->getType($expr->var);
		if (
			!$offsetAccessibleType->isArray()->yes()
			&& (new ObjectType(ArrayAccess::class))->isSuperTypeOf($offsetAccessibleType)->yes()
		) {
			return NullsafeShortCircuitingHelper::getType(
				$scope,
				$expr->var,
				$scope->getType(
					new MethodCall(
						$expr->var,
						new Identifier('offsetGet'),
						[
							new Arg($expr->dim),
						],
					),
				),
			);
		}

		$offsetType = $scope->getType($expr->dim);
		return NullsafeShortCircuitingHelper::getType(
			$scope,
			$expr->var,
			$offsetAccessibleType->getOffsetValueType($offsetType),
		);
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$beforeScope = $scope;
		if ($expr->dim === null) {
			$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, $context->enterDeep());

			return $this->composeResult($nodeScopeResolver, $stmt, $expr, null, $varResult, $storage, $context, $beforeScope);
		}

		$dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->dim, $scope, $storage, $nodeCallback, $context->enterDeep());
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $dimResult->getScope(), $storage, $nodeCallback, $context->enterDeep());

		return $this->composeResult($nodeScopeResolver, $stmt, $expr, $dimResult, $varResult, $storage, $context, $beforeScope);
	}

	/**
	 * Builds the offset read's ExpressionResult from the already-walked
	 * dimension and receiver results - the chain is not re-walked (only the
	 * ArrayAccess offsetGet simulation runs, over synthetic nodes).
	 * processExpr() routes through this; AssignHandler::prepareTarget() calls it
	 * to price a read-modify-write target from the write walk's child results.
	 */
	public function composeResult(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, ArrayDimFetch $expr, ?ExpressionResult $dimResult, ExpressionResult $varResult, ExpressionResultStorage $storage, ExpressionContext $context, MutatingScope $beforeScope): ExpressionResult
	{
		$scope = $varResult->getScope();
		if ($expr->dim === null || $dimResult === null) {
			return $this->expressionResultFactory->create(
				$scope,
				beforeScope: $beforeScope,
				expr: $expr,
				hasYield: $varResult->hasYield(),
				isAlwaysTerminating: $varResult->isAlwaysTerminating(),
				throwPoints: $varResult->getThrowPoints(),
				impurePoints: $varResult->getImpurePoints(),
				containsNullsafe: $varResult->containsNullsafe(),
			);
		}

		$throwPoints = array_merge($dimResult->getThrowPoints(), $varResult->getThrowPoints());
		$impurePoints = array_merge($dimResult->getImpurePoints(), $varResult->getImpurePoints());

		$varType = $varResult->getType();
		if (!$varType->isArray()->yes() && !(new ObjectType(ArrayAccess::class))->isSuperTypeOf($varType)->no()) {
			$throwPoints = array_merge($throwPoints, $nodeScopeResolver->processExprNode(
				$stmt,
				new MethodCall(new TypeExpr($varType), 'offsetGet'),
				$scope,
				$storage,
				new NoopNodeCallback(),
				$context,
			)->getThrowPoints());
		}

		return $this->expressionResultFactory->create(
			$scope,
			beforeScope: $beforeScope,
			expr: $expr,
			hasYield: $dimResult->hasYield() || $varResult->hasYield(),
			isAlwaysTerminating: $dimResult->isAlwaysTerminating() || $varResult->isAlwaysTerminating(),
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
			containsNullsafe: $varResult->containsNullsafe(),
			issetabilityDescriptor: IssetabilityDescriptor::offset($varResult, $dimResult),
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
