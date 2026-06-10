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
use PHPStan\Analyser\ExprHandler\Helper\ConditionalExpressionHolderHelper;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NewWorld;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\ResultAwareScope;
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
use function array_merge;
use function array_reverse;
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
			$leftNormalized = $leftTypes->normalize($scope);
			$rightNormalized = $rightTypes->normalize($rightScope);
			$types = $leftNormalized->intersectWith($rightNormalized);
			$types = $this->conditionalExpressionHolderHelper->augmentDisjunctionTypes($scope, $rightScope, $leftNormalized, $rightNormalized, $expr->left, $expr->right, false, $types);
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
				if ($this->allExpressionsTrackable($truthyLeftTypes)) {
					$leftTypesForHolders = new SpecifiedTypes($truthyLeftTypes->getSureNotTypes(), $truthyLeftTypes->getSureTypes());
				}
			}
			if ($rightTypesForHolders->getSureTypes() === [] && $rightTypesForHolders->getSureNotTypes() === []) {
				$truthyRightTypes = $typeSpecifier->specifyTypesInCondition($rightScope, $expr->right, TypeSpecifierContext::createTruthy());
				if ($this->allExpressionsTrackable($truthyRightTypes)) {
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
			return $result->setNewConditionalExpressionHolders($this->conditionalExpressionHolderHelper->mergeConditionalHolders([
				$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scope, $leftTypesForHolders, $rightTypesForHolders, false, true, $rightScope, $expr->right),
				$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scope, $rightTypesForHolders, $leftTypesForHolders, false, true, $scope, $expr->left),
				$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scope, $leftTypesForHolders, $rightTypesForHolders, true, true, $rightScope, $expr->right),
				$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scope, $rightTypesForHolders, $leftTypesForHolders, true, true, $scope, $expr->left),
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

		// the embedded right scope answers the rules' getType()/getNativeType()/
		// narrowing asks about the right operand — in the new world those must go
		// through the fiber so the stored right result answers them
		$rightScopeForNode = NewWorld::isEnabled() ? $leftTruthyScope->toFiberScope() : $leftTruthyScope;
		$nodeScopeResolver->callNodeCallbackWithExpression($nodeCallback, new BooleanAndNode($expr, $rightScopeForNode), $scope, $storage, $context);

		// the single-pass payoff: the right side was *evaluated* on the left-truthy
		// scope, so its result already is what the old resolveType had to rebuild by
		// re-processing the left side on a throwaway storage — no re-walk, no
		// BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH
		$typeCallback = static function (Expr $e, MutatingScope $s) use ($leftResult, $rightResult): Type {
			if (!$e instanceof BooleanAnd && !$e instanceof LogicalAnd) {
				throw new ShouldNotHappenException();
			}

			$leftBooleanType = $leftResult->getTypeForScope($s)->toBoolean();
			if ($leftBooleanType->isFalse()->yes()) {
				return new ConstantBooleanType(false);
			}

			$rightBooleanType = $rightResult->getTypeForScope($s)->toBoolean();
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
		};

		return new ExpressionResult(
			$leftMergedWithRightScope,
			hasYield: $leftResult->hasYield() || $rightResult->hasYield(),
			isAlwaysTerminating: $leftResult->isAlwaysTerminating(),
			throwPoints: array_merge($leftResult->getThrowPoints(), $rightResult->getThrowPoints()),
			impurePoints: array_merge($leftResult->getImpurePoints(), $rightResult->getImpurePoints()),
			// incremental truthy scope: the right operand was evaluated on the
			// left-truthy scope, so its truthy scope IS the whole conjunction's —
			// no re-derivation, no cross-arm combination (and no representational
			// drift from re-uniting per-arm types). The falsey scope cannot be
			// composed this way (¬(A && B) needs both arms) — specify path.
			truthyScopeCallback: static fn (): MutatingScope => $rightResult->getTruthyScope(),
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: $this->createSpecifyTypesCallback($nodeScopeResolver, $stmt, $leftResult, $rightResult, $leftTruthyScope),
		);
	}

	/**
	 * New-world copy of specifyTypes(): child narrowing comes from the child
	 * ExpressionResults — the recursion is structural, so deep chains compose
	 * linearly and the flattened fast path is not needed. The normalize/
	 * conditional-holder helper code resolves narrowing originals with
	 * $scope->getType() — those asks are priced through adapters seeded with
	 * the operand results (fresh storage per ask, NEW_WORLD.md §3.11).
	 *
	 * @return callable(Expr, MutatingScope, TypeSpecifierContext): SpecifiedTypes
	 */
	private function createSpecifyTypesCallback(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, ExpressionResult $leftResult, ExpressionResult $rightResult, MutatingScope $leftTruthyScope): callable
	{
		return function (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx) use ($nodeScopeResolver, $stmt, $leftResult, $rightResult, $leftTruthyScope): SpecifiedTypes {
			if (!$e instanceof BooleanAnd && !$e instanceof LogicalAnd) {
				throw new ShouldNotHappenException();
			}

			if ($ctx->null()) {
				return (new SpecifiedTypes([], []))->setRootExpr($e);
			}

			// each adapter is seeded only with the result evaluated on its base
			// scope — a result's memoized type is its evaluation-point type, so
			// seeding it under another base would answer asks about narrowing
			// originals with already-narrowed types. Other asks re-process on the
			// base scope (ResultAwareScope tier 4)
			$adapterStorage = new ExpressionResultStorage();
			$scopeAdapter = $s->toResultAwareScope([$s->getNodeKey($e->left) => $leftResult], $nodeScopeResolver, $stmt, $adapterStorage);
			$rightScopeAdapter = $leftTruthyScope->toResultAwareScope([$s->getNodeKey($e->right) => $rightResult], $nodeScopeResolver, $stmt, $adapterStorage);

			$leftTypes = $this->specifyChildTypes($leftResult, $e->left, $s, $scopeAdapter, $ctx)->setRootExpr($e);
			$rightTypes = $this->specifyChildTypes($rightResult, $e->right, $leftTruthyScope, $rightScopeAdapter, $ctx)->setRootExpr($e);
			if ($ctx->true()) {
				$types = $leftTypes->unionWith($rightTypes);
			} else {
				$leftNormalized = $leftTypes->normalize($scopeAdapter);
				$rightNormalized = $rightTypes->normalize($rightScopeAdapter);
				$types = $leftNormalized->intersectWith($rightNormalized);
				$types = $this->conditionalExpressionHolderHelper->augmentDisjunctionTypes($scopeAdapter, $rightScopeAdapter, $leftNormalized, $rightNormalized, $e->left, $e->right, false, $types);
			}
			if ($ctx->false()) {
				$leftTypesForHolders = $leftTypes;
				$rightTypesForHolders = $rightTypes;
				// In a mixed truthy-and-false context, re-derive empty holders from the falsey narrowing.
				if ($ctx->truthy()) {
					if ($leftTypesForHolders->getSureTypes() === [] && $leftTypesForHolders->getSureNotTypes() === []) {
						$leftTypesForHolders = $this->specifyChildTypes($leftResult, $e->left, $s, $scopeAdapter, TypeSpecifierContext::createFalsey())->setRootExpr($e);
					}
					if ($rightTypesForHolders->getSureTypes() === [] && $rightTypesForHolders->getSureNotTypes() === []) {
						$rightTypesForHolders = $this->specifyChildTypes($rightResult, $e->right, $leftTruthyScope, $rightScopeAdapter, TypeSpecifierContext::createFalsey())->setRootExpr($e);
					}
				}
				// For arms still empty (e.g. isset() on an array dim fetch), derive conditions
				// from the truthy narrowing instead, swapping sure/sureNot types.
				if ($leftTypesForHolders->getSureTypes() === [] && $leftTypesForHolders->getSureNotTypes() === []) {
					$truthyLeftTypes = $this->specifyChildTypes($leftResult, $e->left, $s, $scopeAdapter, TypeSpecifierContext::createTruthy());
					if ($this->allExpressionsTrackable($truthyLeftTypes)) {
						$leftTypesForHolders = new SpecifiedTypes($truthyLeftTypes->getSureNotTypes(), $truthyLeftTypes->getSureTypes());
					}
				}
				if ($rightTypesForHolders->getSureTypes() === [] && $rightTypesForHolders->getSureNotTypes() === []) {
					$truthyRightTypes = $this->specifyChildTypes($rightResult, $e->right, $leftTruthyScope, $rightScopeAdapter, TypeSpecifierContext::createTruthy());
					if ($this->allExpressionsTrackable($truthyRightTypes)) {
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
				return $result->setNewConditionalExpressionHolders($this->conditionalExpressionHolderHelper->mergeConditionalHolders([
					$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scopeAdapter, $leftTypesForHolders, $rightTypesForHolders, false, true, $rightScopeAdapter, $e->right),
					$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scopeAdapter, $rightTypesForHolders, $leftTypesForHolders, false, true, $scopeAdapter, $e->left),
					$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scopeAdapter, $leftTypesForHolders, $rightTypesForHolders, true, true, $rightScopeAdapter, $e->right),
					$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scopeAdapter, $rightTypesForHolders, $leftTypesForHolders, true, true, $scopeAdapter, $e->left),
				]))->setRootExpr($e);
			}

			return $types;
		};
	}

	/**
	 * A child's narrowing from its ExpressionResult; not-yet-migrated children
	 * take the old-world dispatcher with the adapter scope, keeping their inner
	 * type lookups unguarded.
	 */
	private function specifyChildTypes(ExpressionResult $result, Expr $child, MutatingScope $scope, ResultAwareScope $adapterScope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($result->hasSpecifiedTypesCallback()) {
			return $result->getSpecifiedTypes($scope, $context);
		}

		return $this->typeSpecifier->specifyTypesInCondition($adapterScope, $child, $context);
	}

}
