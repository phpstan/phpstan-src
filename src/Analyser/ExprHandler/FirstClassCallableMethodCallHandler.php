<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_merge;

/**
 * @implements ExprHandler<MethodCall>
 */
#[AutowiredService]
final class FirstClassCallableMethodCallHandler implements ExprHandler
{

	public function __construct(
		private ExpressionResultFactory $expressionResultFactory,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof MethodCall && $expr->isFirstClassCallable();
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
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->var, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
		$scope = $varResult->getScope();
		$throwPoints = $varResult->getThrowPoints();
		$impurePoints = $varResult->getImpurePoints();

		if (!$expr->name instanceof Identifier) {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, ExpressionContext::createDeep());
			$scope = $nameResult->getScope();
			$throwPoints = array_merge($throwPoints, $nameResult->getThrowPoints());
			$impurePoints = array_merge($impurePoints, $nameResult->getImpurePoints());
		}

		return $this->expressionResultFactory->create(
			$expr,
			$scope,
			typeCallback: function (Expr $expr, MutatingScope $scope) use ($varResult): Type {
				if (!$expr->name instanceof Identifier) {
					return new ObjectType(Closure::class);
				}

				$varType = $varResult->getTypeForScope($scope);
				$method = $scope->getMethodReflection($varType, $expr->name->toString());
				if ($method === null) {
					return new ObjectType(Closure::class);
				}

				return $this->initializerExprTypeResolver->createFirstClassCallable(
					$method,
					$method->getVariants(),
					$scope->nativeTypesPromoted,
				);
			},
			hasYield: false,
			isAlwaysTerminating: false,
			throwPoints: $throwPoints,
			impurePoints: $impurePoints,
		);
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if (!$expr->name instanceof Identifier) {
			return new ObjectType(Closure::class);
		}

		$varType = $scope->getType($expr->var);
		$method = $scope->getMethodReflection($varType, $expr->name->toString());
		if ($method === null) {
			return new ObjectType(Closure::class);
		}

		return $this->initializerExprTypeResolver->createFirstClassCallable(
			$method,
			$method->getVariants(),
			$scope->nativeTypesPromoted,
		);
	}

}
