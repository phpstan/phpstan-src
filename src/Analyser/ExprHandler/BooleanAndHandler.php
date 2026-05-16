<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanAndNode;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<BooleanAnd|LogicalAnd>
 */
#[AutowiredService]
final class BooleanAndHandler implements ExprHandler
{

	private const BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH = 4;

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private TypeSpecifier $typeSpecifier,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BooleanAnd || $expr instanceof LogicalAnd;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$leftBooleanType = $scope->getType($expr->left)->toBoolean();
		if ($leftBooleanType->isFalse()->yes()) {
			return new ConstantBooleanType(false);
		}

		if (self::getBooleanExpressionDepth($expr->left) <= self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH) {
			$leftResult = $this->nodeScopeResolver->processExprNode(new Stmt\Expression($expr->left), $expr->left, $scope, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep());
			$rightBooleanType = $leftResult->getTruthyScope()->getType($expr->right)->toBoolean();
		} else {
			$rightBooleanType = $scope->filterByTruthyValue($expr->left)->getType($expr->right)->toBoolean();
		}

		if ($rightBooleanType->isFalse()->yes()) {
			return new ConstantBooleanType(false);
		}

		if (
			$leftBooleanType->isTrue()->yes()
			&& $rightBooleanType->isTrue()->yes()
		) {
			return new ConstantBooleanType(true);
		}

		return new BooleanType();
	}

	public static function getBooleanExpressionDepth(Expr $expr, int $depth = 0): int
	{
		while (
			$expr instanceof BooleanOr
			|| $expr instanceof LogicalOr
			|| $expr instanceof BooleanAnd
			|| $expr instanceof LogicalAnd
		) {
			return self::getBooleanExpressionDepth($expr->left, $depth + 1);
		}

		return $depth;
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

		$leftTruthySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $expr->left, TypeSpecifierContext::createTruthy());

		return new ExpressionResult(
			$leftMergedWithRightScope,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating(),
			throwPoints: array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
			truthyScopeCallback: static fn (): MutatingScope => self::computeTruthyScope($rightResult->getScope(), $expr->right, $leftTruthySpecifiedTypes, $leftTruthyScope),
			falseyScopeCallback: static fn (): MutatingScope => $leftMergedWithRightScope->filterByFalseyValue($expr),
		);
	}

	private static function computeTruthyScope(
		MutatingScope $rightScope,
		Expr $rightExpr,
		SpecifiedTypes $leftTruthySpecifiedTypes,
		MutatingScope $leftTruthyScope,
	): MutatingScope
	{
		$scope = $rightScope->filterByTruthyValue($rightExpr);

		foreach ($leftTruthySpecifiedTypes->getSureNotTypes() as [$exprNode, $sureNotType]) {
			if (!$leftTruthyScope->getType($exprNode)->equals($rightScope->getType($exprNode))) {
				continue;
			}

			$exprType = $scope->getType($exprNode);
			if (TypeCombinator::remove($exprType, $sureNotType) === $exprType) {
				continue;
			}

			$scope = $scope->removeTypeFromExpression($exprNode, $sureNotType);
		}

		return $scope;
	}

}
