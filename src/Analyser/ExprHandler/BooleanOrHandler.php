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
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanOrNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
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

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		// For deep BooleanOr chains, flatten and process all arms at once
		// to avoid O(n^2) recursive filterByFalseyValue calls
		if (BooleanAndHandler::getBooleanExpressionDepth($expr) > self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH) {
			return $typeSpecifier->specifyTypesForFlattenedBooleanOr($scope, $expr, $context);
		}

		$leftTypes = $typeSpecifier->specifyTypesInCondition($scope, $expr->left, $context)->setRootExpr($expr);
		$rightScope = $scope->filterByFalseyValue($expr->left);
		$rightTypes = $typeSpecifier->specifyTypesInCondition($rightScope, $expr->right, $context)->setRootExpr($expr);

		if ($context->true()) {
			if (
				$scope->getType($expr->left)->toBoolean()->isFalse()->yes()
			) {
				$types = $rightTypes->normalize($rightScope);
			} elseif (
				$scope->getType($expr->left)->toBoolean()->isTrue()->yes()
				|| $scope->getType($expr->right)->toBoolean()->isFalse()->yes()
			) {
				$types = $leftTypes->normalize($scope);
			} else {
				$leftNormalized = $leftTypes->normalize($scope);
				$rightNormalized = $rightTypes->normalize($rightScope);
				$types = $leftNormalized->intersectWith($rightNormalized);
				$types = $typeSpecifier->augmentBooleanOrTruthyWithConditionalHolders($scope, $rightScope, $expr, $types);
				$types = $typeSpecifier->augmentDisjunctionTypes($scope, $rightScope, $leftNormalized, $rightNormalized, $expr->left, $expr->right, true, $types);
			}
		} else {
			$types = $leftTypes->unionWith($rightTypes);
		}

		if ($context->true()) {
			$result = new SpecifiedTypes(
				$types->getSureTypes(),
				$types->getSureNotTypes(),
			);
			if ($types->shouldOverwrite()) {
				$result = $result->setAlwaysOverwriteTypes();
			}
			return $result->setNewConditionalExpressionHolders($typeSpecifier->mergeConditionalHolders([
				$typeSpecifier->processBooleanConditionalTypes($scope, $leftTypes, $rightTypes, false, false, $rightScope, $expr->right),
				$typeSpecifier->processBooleanConditionalTypes($scope, $rightTypes, $leftTypes, false, false, $scope, $expr->left),
				$typeSpecifier->processBooleanConditionalTypes($scope, $leftTypes, $rightTypes, true, false, $rightScope, $expr->right),
				$typeSpecifier->processBooleanConditionalTypes($scope, $rightTypes, $leftTypes, true, false, $scope, $expr->left),
			]))->setRootExpr($expr);
		}

		return $types;
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
