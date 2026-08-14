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
use PHPStan\Node\Expr\UnsetOffsetExpr;

/**
 * @implements ExprHandler<UnsetOffsetExpr>
 */
#[AutowiredService]
final class UnsetOffsetExprHandler implements ExprHandler
{

	public function __construct(private VirtualExprResultHelper $virtualExprResultHelper)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof UnsetOffsetExpr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		// virtual node: callers only read the type, computed lazily by the
		// typeCallback. The (synthetic) sub-expressions are processed here - by
		// on-demand time their real leaves are already stored, so this reads them
		// back; the typeCallback then reads the ExpressionResults instead of
		// Scope::getType(). A null specifyTypesCallback falls back to default
		// narrowing in TypeSpecifier, matching the old specifyDefaultTypes().
		$varResult = $nodeScopeResolver->processExprNode($stmt, $expr->getVar(), $scope, $storage, $nodeCallback, $context);
		$dimResult = $nodeScopeResolver->processExprNode($stmt, $expr->getDim(), $scope, $storage, $nodeCallback, $context);

		return $this->virtualExprResultHelper->createUnsetOffsetExprResult($scope, $expr, $varResult, $dimResult);
	}

}
