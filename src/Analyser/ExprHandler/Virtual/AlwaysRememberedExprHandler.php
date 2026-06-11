<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<AlwaysRememberedExpr>
 */
#[AutowiredService]
final class AlwaysRememberedExprHandler implements ExprHandler
{

	public function __construct(private ExpressionResultFactory $expressionResultFactory)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof AlwaysRememberedExpr;
	}

	public function processExpr(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		Expr $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
	): ExpressionResult
	{
		$innerExpr = $expr->getExpr();
		$innerResult = $nodeScopeResolver->processExprNode($stmt, $innerExpr, $scope, $storage, $nodeCallback, $context);
		$scope = $innerResult->getScope();

		return $this->expressionResultFactory->create(
			$scope,
			hasYield: $innerResult->hasYield(),
			isAlwaysTerminating: $innerResult->isAlwaysTerminating(),
			throwPoints: $innerResult->getThrowPoints(),
			impurePoints: $innerResult->getImpurePoints(),
			truthyScopeCallback: static fn (): MutatingScope => $scope->filterByTruthyValue($innerExpr),
			falseyScopeCallback: static fn (): MutatingScope => $scope->filterByFalseyValue($innerExpr),
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $scope->nativeTypesPromoted ? $expr->getNativeExprType() : $expr->getExprType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
