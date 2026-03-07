<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanAndNode;
use PHPStan\Type\NeverType;
use function array_merge;

/**
 * @implements ExprHandler<BooleanAnd|LogicalAnd>
 */
#[AutowiredService]
final class BooleanAndHandler implements ExprHandler
{

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BooleanAnd || $expr instanceof LogicalAnd;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$leftResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
		$leftTruthyScope = $leftResult->getTruthyScope();
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $leftTruthyScope, $storage, $nodeCallback, $context);
		$rightExprType = $rightResult->getScope()->getType($expr->right);
		if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
			$leftMergedWithRightScope = $leftResult->getFalseyScope();
		} else {
			$leftMergedWithRightScope = $leftResult->getScope()->mergeWith($rightResult->getScope());
		}

		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new BooleanAndNode($expr, $leftTruthyScope), $scope, $storage, $context);

		return new ExpressionResult(
			$leftMergedWithRightScope,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating(),
			throwPoints: array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
			truthyScopeCallback: static fn (): MutatingScope => $rightResult->getScope()->filterByTruthyValue($expr->right),
			falseyScopeCallback: static fn (): MutatingScope => $leftMergedWithRightScope->filterByFalseyValue($expr),
		);
	}

}
