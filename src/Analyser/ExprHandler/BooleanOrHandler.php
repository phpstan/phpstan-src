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
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanOrNode;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_merge;

/**
 * @implements ExprHandler<BooleanOr|LogicalOr>
 */
#[AutowiredService]
final class BooleanOrHandler implements ExprHandler
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
		return $expr instanceof BooleanOr || $expr instanceof LogicalOr;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		$leftBooleanType = $scope->getType($expr->left)->toBoolean();
		if ($leftBooleanType->isTrue()->yes()) {
			return new ConstantBooleanType(true);
		}

		if (BooleanAndHandler::getBooleanExpressionDepth($expr->left) <= self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH) {
			$leftResult = $this->nodeScopeResolver->processExprNode(new Stmt\Expression($expr->left), $expr->left, $scope, new ExpressionResultStorage(), new NoopNodeCallback(), ExpressionContext::createDeep());
			$rightBooleanType = $leftResult->getFalseyScope()->getType($expr->right)->toBoolean();
		} else {
			$rightBooleanType = $scope->filterByFalseyValue($expr->left)->getType($expr->right)->toBoolean();
		}

		if ($rightBooleanType->isTrue()->yes()) {
			return new ConstantBooleanType(true);
		}

		if (
			$leftBooleanType->isFalse()->yes()
			&& $rightBooleanType->isFalse()->yes()
		) {
			return new ConstantBooleanType(false);
		}

		return new BooleanType();
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

		$leftFalseySpecifiedTypes = $this->typeSpecifier->specifyTypesInCondition($scope, $expr->left, TypeSpecifierContext::createFalsey());

		return new ExpressionResult(
			$leftMergedWithRightScope,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating(),
			throwPoints: array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
			truthyScopeCallback: static fn (): MutatingScope => $leftMergedWithRightScope->filterByTruthyValue($expr),
			falseyScopeCallback: static fn (): MutatingScope => self::computeFalseyScope($rightResult->getScope(), $expr->right, $leftFalseySpecifiedTypes, $leftFalseyScope),
		);
	}

	private static function computeFalseyScope(
		MutatingScope $rightScope,
		Expr $rightExpr,
		SpecifiedTypes $leftFalseySpecifiedTypes,
		MutatingScope $leftFalseyScope,
	): MutatingScope
	{
		$scope = $rightScope->filterByFalseyValue($rightExpr);

		foreach ($leftFalseySpecifiedTypes->getSureNotTypes() as [$exprNode, $sureNotType]) {
			if (!$leftFalseyScope->getType($exprNode)->equals($rightScope->getType($exprNode))) {
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
