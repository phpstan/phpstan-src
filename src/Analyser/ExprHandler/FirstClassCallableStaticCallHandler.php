<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
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
use PHPStan\Type\Type;

/**
 * @implements ExprHandler<StaticCall>
 */
#[AutowiredService]
final class FirstClassCallableStaticCallHandler implements ExprHandler
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof StaticCall && $expr->isFirstClassCallable();
	}

	public function processExpr(
		NodeScopeResolver $nodeScopeResolver,
		Stmt $stmt,
		Expr $expr,
		MutatingScope $scope,
		ExpressionResultStorage $storage,
		callable $nodeCallback,
		ExpressionContext $context,
		?Type $overriddenType,
	): ExpressionResult
	{
		// handled in NodeScopeResolver before ExprHandlers are called
		throw new ShouldNotHappenException();
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		return $this->initializerExprTypeResolver->getFirstClassCallableType($expr, InitializerExprContext::fromScope($scope), $scope->nativeTypesPromoted);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return (new SpecifiedTypes([], []))->setRootExpr($expr);
	}

}
