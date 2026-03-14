<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<FuncCall>
 */
#[AutowiredService]
final class FirstClassCallableFuncCallHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof FuncCall && $expr->isFirstClassCallable();
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
		$nameResult = null;
		if ($expr->name instanceof Expr) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$scope = $nameResult->getScope();
		}

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: function (Expr $expr, MutatingScope $scope) use ($nameResult): Type {
				if ($nameResult !== null) {
					$callableType = $nameResult->getTypeForScope($scope);
					if (!$callableType->isCallable()->yes()) {
						return new ObjectType(Closure::class);
					}

					return $this->initializerExprTypeResolver->createFirstClassCallable(
						null,
						$callableType->getCallableParametersAcceptors($scope),
						$scope->nativeTypesPromoted,
					);
				}

				return $this->initializerExprTypeResolver->getFirstClassCallableType($expr, InitializerExprContext::fromScope($scope), $scope->nativeTypesPromoted);
			},
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: $nameResult !== null ? $nameResult->getThrowPoints() : [],
			impurePoints: $nameResult !== null ? $nameResult->getImpurePoints() : [],
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if ($expr->name instanceof Expr) {
			$callableType = $scope->getType($expr->name);
			if (!$callableType->isCallable()->yes()) {
				return new ObjectType(Closure::class);
			}

			return $this->initializerExprTypeResolver->createFirstClassCallable(
				null,
				$callableType->getCallableParametersAcceptors($scope),
				$scope->nativeTypesPromoted,
			);
		}

		return $this->initializerExprTypeResolver->getFirstClassCallableType($expr, InitializerExprContext::fromScope($scope), $scope->nativeTypesPromoted);
	}

}
