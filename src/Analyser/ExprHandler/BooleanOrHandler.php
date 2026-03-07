<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanOrNode;
use PHPStan\Type\NeverType;
use function array_merge;

/**
 * @implements ExprHandler<BooleanOr|LogicalOr>
 */
#[AutowiredService]
final class BooleanOrHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BooleanOr || $expr instanceof LogicalOr;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$leftResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
		$leftFalseyScope = $leftResult->getFalseyScope();
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $leftFalseyScope, $storage, $nodeCallback, $context);
		$rightExprType = $rightResult->getScope()->getType($expr->right);
		if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
			$leftMergedWithRightScope = $leftResult->getTruthyScope();
		} else {
			$leftMergedWithRightScope = $leftResult->getScope()->mergeWith($rightResult->getScope());
		}

		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new BooleanOrNode($expr, $leftFalseyScope), $scope, $storage, $context);

		return new ExpressionResult(
			$leftMergedWithRightScope,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating(),
			throwPoints: array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
			truthyScopeCallback: static fn (): MutatingScope => $leftMergedWithRightScope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => $rightResult->getScope()->filterByFalseyValue($expr->right),
		);
	}

}
