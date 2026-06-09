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
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\BooleanAndNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
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

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		// For deep BooleanAnd chains in truthy context, flatten and
		// process all arms at once to avoid O(N²) recursive
		// filterByTruthyValue calls.
		if (
			$context->true()
			&& self::getBooleanExpressionDepth($expr) > self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH
		) {
			return $typeSpecifier->specifyTypesForFlattenedBooleanAnd($scope, $expr, $context);
		}

		$leftTypes = $typeSpecifier->specifyTypesInCondition($scope, $expr->left, $context)->setRootExpr($expr);
		$rightScope = $scope->filterByTruthyValue($expr->left);
		$rightTypes = $typeSpecifier->specifyTypesInCondition($rightScope, $expr->right, $context)->setRootExpr($expr);
		if ($context->true()) {
			$types = $leftTypes->unionWith($rightTypes);
		} else {
			$leftNormalized = $leftTypes->normalize($scope);
			$rightNormalized = $rightTypes->normalize($rightScope);
			$types = $leftNormalized->intersectWith($rightNormalized);
			$types = $typeSpecifier->augmentDisjunctionTypes($scope, $rightScope, $leftNormalized, $rightNormalized, $expr->left, $expr->right, false, $types);
		}
		if ($context->false()) {
			$leftTypesForHolders = $leftTypes;
			$rightTypesForHolders = $rightTypes;
			// In a mixed truthy-and-false context, re-derive empty holders from the falsey narrowing.
			if ($context->truthy()) {
				if ($leftTypesForHolders->getSureTypes() === [] && $leftTypesForHolders->getSureNotTypes() === []) {
					$leftTypesForHolders = $typeSpecifier->specifyTypesInCondition($scope, $expr->left, TypeSpecifierContext::createFalsey())->setRootExpr($expr);
				}
				if ($rightTypesForHolders->getSureTypes() === [] && $rightTypesForHolders->getSureNotTypes() === []) {
					$rightTypesForHolders = $typeSpecifier->specifyTypesInCondition($rightScope, $expr->right, TypeSpecifierContext::createFalsey())->setRootExpr($expr);
				}
			}
			// For arms still empty (e.g. isset() on an array dim fetch), derive conditions
			// from the truthy narrowing instead, swapping sure/sureNot types.
			if ($leftTypesForHolders->getSureTypes() === [] && $leftTypesForHolders->getSureNotTypes() === []) {
				$truthyLeftTypes = $typeSpecifier->specifyTypesInCondition($scope, $expr->left, TypeSpecifierContext::createTruthy());
				if ($typeSpecifier->allExpressionsTrackable($truthyLeftTypes)) {
					$leftTypesForHolders = new SpecifiedTypes($truthyLeftTypes->getSureNotTypes(), $truthyLeftTypes->getSureTypes());
				}
			}
			if ($rightTypesForHolders->getSureTypes() === [] && $rightTypesForHolders->getSureNotTypes() === []) {
				$truthyRightTypes = $typeSpecifier->specifyTypesInCondition($rightScope, $expr->right, TypeSpecifierContext::createTruthy());
				if ($typeSpecifier->allExpressionsTrackable($truthyRightTypes)) {
					$rightTypesForHolders = new SpecifiedTypes($truthyRightTypes->getSureNotTypes(), $truthyRightTypes->getSureTypes());
				}
			}
			$result = new SpecifiedTypes(
				$types->getSureTypes(),
				$types->getSureNotTypes(),
			);
			if ($types->shouldOverwrite()) {
				$result = $result->setAlwaysOverwriteTypes();
			}
			return $result->setNewConditionalExpressionHolders($typeSpecifier->mergeConditionalHolders([
				$typeSpecifier->processBooleanConditionalTypes($scope, $leftTypesForHolders, $rightTypesForHolders, false, true, $rightScope, $expr->right),
				$typeSpecifier->processBooleanConditionalTypes($scope, $rightTypesForHolders, $leftTypesForHolders, false, true, $scope, $expr->left),
				$typeSpecifier->processBooleanConditionalTypes($scope, $leftTypesForHolders, $rightTypesForHolders, true, true, $rightScope, $expr->right),
				$typeSpecifier->processBooleanConditionalTypes($scope, $rightTypesForHolders, $leftTypesForHolders, true, true, $scope, $expr->left),
			]))->setRootExpr($expr);
		}

		return $types;
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
