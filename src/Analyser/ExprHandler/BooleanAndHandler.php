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
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\ConditionalExpressionHolderHelper;
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
use PHPStan\Type\TypeCombinator;
use function array_filter;
use function array_merge;
use function array_reverse;
use function array_values;
use function is_string;

/**
 * @implements ExprHandler<BooleanAnd|LogicalAnd>
 */
#[AutowiredService]
final class BooleanAndHandler implements ExprHandler
{

	private const BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH = 4;

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private ConditionalExpressionHolderHelper $conditionalExpressionHolderHelper,
		private ExpressionResultFactory $expressionResultFactory,
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
			return $this->specifyTypesForFlattenedBooleanAnd($typeSpecifier, $scope, $expr, $context);
		}

		$leftTypes = $typeSpecifier->specifyTypesInCondition($scope, $expr->left, $context)->setRootExpr($expr);
		$rightScope = $scope->filterByTruthyValue($expr->left);
		$rightTypes = $typeSpecifier->specifyTypesInCondition($rightScope, $expr->right, $context)->setRootExpr($expr);
		if ($context->true()) {
			$types = $leftTypes->unionWith($rightTypes);
		} else {
			$types = $leftTypes->intersectWith($rightTypes);
			$branchUnionAugment = $this->conditionalExpressionHolderHelper->buildBranchUnionAugment(
				$leftTypes,
				$rightTypes,
				static fn (): MutatingScope => $scope->filterByFalseyValue($expr->left),
				static fn (): MutatingScope => $rightScope->filterByFalseyValue($expr->right),
				$types,
			);
			if ($branchUnionAugment !== null) {
				$types = $types->withDeferredAugment($branchUnionAugment);
			}
		}
		if ($context->false()) {
			// Consequent (holder) narrowings projected by each holder: these must be
			// the genuine falsey narrowing of the arm. When that is empty, the arm
			// has no sound falsey narrowing and must not contribute a consequent.
			$leftHolderTypes = $leftTypes;
			$rightHolderTypes = $rightTypes;
			// In a mixed truthy-and-false context, re-derive empty holders from the falsey narrowing.
			if ($context->truthy()) {
				if ($leftHolderTypes->getSureTypes() === [] && $leftHolderTypes->getSureNotTypes() === []) {
					$leftHolderTypes = $typeSpecifier->specifyTypesInCondition($scope, $expr->left, TypeSpecifierContext::createFalsey())->setRootExpr($expr);
				}
				if ($rightHolderTypes->getSureTypes() === [] && $rightHolderTypes->getSureNotTypes() === []) {
					$rightHolderTypes = $typeSpecifier->specifyTypesInCondition($rightScope, $expr->right, TypeSpecifierContext::createFalsey())->setRootExpr($expr);
				}
			}
			// Condition (antecedent) narrowings: when an arm has no falsey narrowing
			// (e.g. isset() on an array dim fetch), derive the condition from the truthy
			// narrowing by swapping sure/sureNot types. This swap is only sound for the
			// antecedent — the holder-recipe evaluation inverts it back to the truthy
			// narrowing. It must NOT feed the consequent: inverting a comparison's truthy
			// narrowing (e.g. `$a === $b` narrowing `$a` to `$b`'s broad type) would
			// over-narrow the consequent (see regression for `$x === $nonConstantString`).
			$leftCondTypes = $leftHolderTypes;
			$rightCondTypes = $rightHolderTypes;
			if ($leftCondTypes->getSureTypes() === [] && $leftCondTypes->getSureNotTypes() === []) {
				$truthyLeftTypes = $typeSpecifier->specifyTypesInCondition($scope, $expr->left, TypeSpecifierContext::createTruthy());
				if ($this->allExpressionsTrackable($truthyLeftTypes)) {
					$leftCondTypes = new SpecifiedTypes($truthyLeftTypes->getSureNotTypes(), $truthyLeftTypes->getSureTypes());
				}
			}
			if ($rightCondTypes->getSureTypes() === [] && $rightCondTypes->getSureNotTypes() === []) {
				$truthyRightTypes = $typeSpecifier->specifyTypesInCondition($rightScope, $expr->right, TypeSpecifierContext::createTruthy());
				if ($this->allExpressionsTrackable($truthyRightTypes)) {
					$rightCondTypes = new SpecifiedTypes($truthyRightTypes->getSureNotTypes(), $truthyRightTypes->getSureTypes());
				}
			}
			$result = $types->withoutConditionalExpressionHolders();
			$recipes = [
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($leftCondTypes, $rightHolderTypes, false, true, $rightScope, $expr->right),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($rightCondTypes, $leftHolderTypes, false, true, null, $expr->left),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($leftCondTypes, $rightHolderTypes, true, true, $rightScope, $expr->right),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($rightCondTypes, $leftHolderTypes, true, true, null, $expr->left),
			];
			return $result->setConditionalExpressionHolderRecipes(array_values(array_filter($recipes)))->setRootExpr($expr);
		}

		return $types;
	}

	public static function getBooleanExpressionDepth(Expr $expr): int
	{
		$depth = 0;
		while (
			$expr instanceof BooleanOr ||
			$expr instanceof LogicalOr ||
			$expr instanceof BooleanAnd ||
			$expr instanceof LogicalAnd
		) {
			$depth++;
			$expr = $expr->left;
		}
		return $depth;
	}

	/**
	 * Flatten a deep BooleanAnd chain into leaf expressions and process them
	 * without recursive filterByTruthyValue calls.
	 *
	 * @param BooleanAnd|LogicalAnd $expr
	 */
	private function specifyTypesForFlattenedBooleanAnd(
		TypeSpecifier $typeSpecifier,
		MutatingScope $scope,
		Expr $expr,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$arms = [];
		$current = $expr;
		while ($current instanceof BooleanAnd || $current instanceof LogicalAnd) {
			$arms[] = $current->right;
			$current = $current->left;
		}
		$arms[] = $current;
		$arms = array_reverse($arms);

		// Truthy: all arms are true → union all SpecifiedTypes.
		// Collect per-expression types first, then build unions once
		// to avoid O(N²) from incremental growth.
		/** @var array<string, array{Expr, list<Type>}> $sureTypesPerExpr */
		$sureTypesPerExpr = [];
		/** @var array<string, array{Expr, list<Type>}> $sureNotTypesPerExpr */
		$sureNotTypesPerExpr = [];

		foreach ($arms as $arm) {
			$armTypes = $typeSpecifier->specifyTypesInCondition($scope, $arm, $context);
			foreach ($armTypes->getSureTypes() as $exprString => [$exprNode, $type]) {
				$sureTypesPerExpr[$exprString][0] = $exprNode;
				$sureTypesPerExpr[$exprString][1][] = $type;
			}
			foreach ($armTypes->getSureNotTypes() as $exprString => [$exprNode, $type]) {
				$sureNotTypesPerExpr[$exprString][0] = $exprNode;
				$sureNotTypesPerExpr[$exprString][1][] = $type;
			}
		}

		$sureTypes = [];
		foreach ($sureTypesPerExpr as $exprString => [$exprNode, $types]) {
			$sureTypes[$exprString] = [$exprNode, TypeCombinator::union(...$types)];
		}
		$sureNotTypes = [];
		foreach ($sureNotTypesPerExpr as $exprString => [$exprNode, $types]) {
			$sureNotTypes[$exprString] = [$exprNode, TypeCombinator::union(...$types)];
		}

		return (new SpecifiedTypes($sureTypes, $sureNotTypes))->setRootExpr($expr);
	}

	private function allExpressionsTrackable(SpecifiedTypes $types): bool
	{
		foreach ($types->getSureTypes() as [$expr]) {
			if (!$this->isTrackableExpression($expr)) {
				return false;
			}
		}
		foreach ($types->getSureNotTypes() as [$expr]) {
			if (!$this->isTrackableExpression($expr)) {
				return false;
			}
		}

		return $types->getSureTypes() !== [] || $types->getSureNotTypes() !== [];
	}

	private function isTrackableExpression(Expr $expr): bool
	{
		if ($expr instanceof Expr\Variable) {
			return is_string($expr->name);
		}

		return $expr instanceof Expr\PropertyFetch
			|| $expr instanceof Expr\ArrayDimFetch
			|| $expr instanceof Expr\StaticPropertyFetch;
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$leftResult = $nodeScopeResolver->processExprNode($stmt, $expr->left, $scope, $storage, $nodeCallback, $context->enterDeep());
		$leftTruthyScope = $leftResult->getTruthyScope();
		$rightResult = $nodeScopeResolver->processExprNode($stmt, $expr->right, $leftTruthyScope, $storage, $nodeCallback, $context);
		$rightExprType = $rightResult->getType();
		if ($rightExprType instanceof NeverType && $rightExprType->isExplicit()) {
			$leftMergedWithRightScope = $leftResult->getFalseyScope();
		} else {
			$leftMergedWithRightScope = $leftResult->getScope()->mergeWith($rightResult->getScope());
		}

		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new BooleanAndNode($expr, $leftTruthyScope), $scope, $storage, $context);

		return $this->expressionResultFactory->create(
			$leftMergedWithRightScope,
			beforeScope: $scope,
			expr: $expr,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating(),
			throwPoints: array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
			truthyScopeCallback: static fn (): MutatingScope => $rightResult->getScope()->filterByTruthyValue($expr->right),
			falseyScopeCallback: static fn (): MutatingScope => $leftMergedWithRightScope->filterByFalseyValue($expr),
		);
	}

}
