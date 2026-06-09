<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<FuncCall>
 */
#[AutowiredService]
final class FirstClassCallableFuncCallHandler implements ExprHandler
{

	public function __construct(
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
		// handled in NodeScopeResolver before ExprHandlers are called
		throw new ShouldNotHappenException();
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

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

}
