<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Virtual;

use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\VirtualExprResultHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\NativeTypeExpr;

/**
 * @implements ExprHandler<NativeTypeExpr>
 */
#[AutowiredService]
final class NativeTypeExprHandler implements ExprHandler
{

	public function __construct(private VirtualExprResultHelper $virtualExprResultHelper)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof NativeTypeExpr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		// because this is a virtual node handler, the caller will only be interested in the type
		// we don't need to process the inner expr
		return $this->virtualExprResultHelper->createTypeExprResult($scope, $expr);
	}

}
