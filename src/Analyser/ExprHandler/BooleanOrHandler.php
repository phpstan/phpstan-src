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
use PHPStan\Analyser\ExprHandler\Helper\ConditionalExpressionHolderHelper;
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
use PHPStan\Type\TypeCombinator;
use function array_key_first;
use function array_key_last;
use function array_merge;
use function array_reverse;
use function count;

/**
 * @implements ExprHandler<BooleanOr|LogicalOr>
 */
#[AutowiredService]
final class BooleanOrHandler implements ExprHandler
{

	private const BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH = 4;

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private ConditionalExpressionHolderHelper $conditionalExpressionHolderHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof BooleanOr || $expr instanceof LogicalOr;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		// For deep BooleanOr chains, resolve the boolean type by iterating the flattened arms while
		// threading the falsey scope, instead of recursing into the left operand and re-narrowing the
		// whole chain at each level - the latter is O(n^2) (and worse) in the number of arms.
		if (BooleanAndHandler::getBooleanExpressionDepth($expr) > self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH) {
			return $this->resolveTypeForFlattenedBooleanOr($scope, $expr);
		}

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

	/**
	 * The whole chain is true if any arm is true (given the previous arms are false), false if every
	 * arm is false, and bool otherwise. Threading the falsey scope arm by arm keeps this O(n), matching
	 * the recursive resolveType() result without re-narrowing the whole left chain at each level.
	 *
	 * @param BooleanOr|LogicalOr $expr
	 */
	private function resolveTypeForFlattenedBooleanOr(MutatingScope $scope, Expr $expr): Type
	{
		$arms = [];
		$current = $expr;
		while ($current instanceof BooleanOr || $current instanceof LogicalOr) {
			$arms[] = $current->right;
			$current = $current->left;
		}
		$arms[] = $current;
		$arms = array_reverse($arms);

		$allArmsAreFalse = true;
		$armScope = $scope;
		$lastArmKey = array_key_last($arms);
		foreach ($arms as $key => $arm) {
			$armBooleanType = $armScope->getType($arm)->toBoolean();
			if ($armBooleanType->isTrue()->yes()) {
				return new ConstantBooleanType(true);
			}
			if (!$armBooleanType->isFalse()->yes()) {
				$allArmsAreFalse = false;
			}
			if ($key === $lastArmKey) {
				continue;
			}
			$armScope = $armScope->filterByFalseyValue($arm);
		}

		return $allArmsAreFalse ? new ConstantBooleanType(false) : new BooleanType();
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		// For deep BooleanOr chains, flatten and process all arms at once
		// to avoid O(n^2) recursive filterByFalseyValue calls
		if (BooleanAndHandler::getBooleanExpressionDepth($expr) > self::BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH) {
			return $this->specifyTypesForFlattenedBooleanOr($typeSpecifier, $scope, $expr, $context);
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
				$types = $this->augmentBooleanOrTruthyWithConditionalHolders($typeSpecifier, $scope, $rightScope, $expr, $types);
				$types = $this->conditionalExpressionHolderHelper->augmentDisjunctionTypes($scope, $rightScope, $leftNormalized, $rightNormalized, $expr->left, $expr->right, true, $types);
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
			return $result->setNewConditionalExpressionHolders($this->conditionalExpressionHolderHelper->mergeConditionalHolders([
				$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scope, $leftTypes, $rightTypes, false, false, $rightScope, $expr->right),
				$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scope, $rightTypes, $leftTypes, false, false, $scope, $expr->left),
				$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scope, $leftTypes, $rightTypes, true, false, $rightScope, $expr->right),
				$this->conditionalExpressionHolderHelper->processBooleanConditionalTypes($scope, $rightTypes, $leftTypes, true, false, $scope, $expr->left),
			]))->setRootExpr($expr);
		}

		return $types;
	}

	/**
	 * Flatten a deep BooleanOr chain into leaf expressions and process them
	 * without recursive filterByFalseyValue calls. This reduces O(n^2) to O(n)
	 * for chains with many arms (e.g., 80+ === comparisons in ||).
	 */
	private function specifyTypesForFlattenedBooleanOr(
		TypeSpecifier $typeSpecifier,
		MutatingScope $scope,
		BooleanOr|LogicalOr $expr,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		// Collect all leaf expressions from the chain
		$arms = [];
		$current = $expr;
		while ($current instanceof BooleanOr || $current instanceof LogicalOr) {
			$arms[] = $current->right;
			$current = $current->left;
		}
		$arms[] = $current; // leftmost leaf
		$arms = array_reverse($arms);

		if ($context->false() || $context->falsey()) {
			// Falsey: all arms are false → union all SpecifiedTypes.
			// Collect per-expression types first, then build unions once
			// to avoid O(N²) from incremental TypeCombinator::union() growth.
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
				$sureTypes[$exprString] = [$exprNode, TypeCombinator::intersect(...$types)];
			}
			$sureNotTypes = [];
			foreach ($sureNotTypesPerExpr as $exprString => [$exprNode, $types]) {
				$sureNotTypes[$exprString] = [$exprNode, TypeCombinator::union(...$types)];
			}

			return (new SpecifiedTypes($sureTypes, $sureNotTypes))->setRootExpr($expr);
		}

		// Truthy: at least one arm is true → intersect all normalized SpecifiedTypes
		$armSpecifiedTypes = [];
		foreach ($arms as $arm) {
			$armTypes = $typeSpecifier->specifyTypesInCondition($scope, $arm, $context);
			$armSpecifiedTypes[] = $armTypes->normalize($scope);
		}

		$types = $armSpecifiedTypes[0];
		for ($i = 1; $i < count($armSpecifiedTypes); $i++) {
			$types = $types->intersectWith($armSpecifiedTypes[$i]);
		}

		$result = new SpecifiedTypes(
			$types->getSureTypes(),
			$types->getSureNotTypes(),
		);
		if ($types->shouldOverwrite()) {
			$result = $result->setAlwaysOverwriteTypes();
		}

		return $result->setRootExpr($expr);
	}

	/**
	 * For `if ($a || $b)` truthy, expressions narrowed by stored conditional
	 * holders (e.g. `$a = $obj instanceof ClassA;` records "when `$a` is
	 * truthy, `$obj` is `ClassA`") need to be projected into the OR-truthy
	 * scope as the union of the per-arm narrowings. specifyTypesInCondition
	 * for each arm only looks at the boolean variable itself, so the held
	 * narrowing of `$obj` would otherwise be invisible until a later check
	 * pins one of the booleans down.
	 *
	 * For each conditional-holder target $T:
	 * - resolve $T's type in the left-truthy and right-truthy filtered scopes
	 * - if both narrow $T strictly below the original, add `$T : leftT|rightT`
	 *   as a sure type to the OR-truthy result
	 *
	 * The asymmetric case (one arm narrows, the other doesn't) is intentionally
	 * skipped: in the OR-truthy scope the arm that didn't narrow could still be
	 * the truthy one, so the sound result is the original (unnarrowed) type.
	 */
	private function augmentBooleanOrTruthyWithConditionalHolders(TypeSpecifier $typeSpecifier, MutatingScope $scope, MutatingScope $rightScope, BooleanOr|LogicalOr $expr, SpecifiedTypes $types): SpecifiedTypes
	{
		$leftTruthyScope = $scope->filterByTruthyValue($expr->left);
		$rightTruthyScope = $rightScope->filterByTruthyValue($expr->right);

		$seen = [];
		foreach ([$scope, $rightScope] as $sourceScope) {
			foreach ($sourceScope->getConditionalExpressions() as $exprString => $holders) {
				if (isset($seen[$exprString])) {
					continue;
				}
				if ($holders === []) {
					continue;
				}
				$seen[$exprString] = true;
				$targetExpr = $holders[array_key_first($holders)]->getTypeHolder()->getExpr();

				// Only project when the target stays Yes-defined in the original
				// scope and in both filtered branches. A sure type implicitly
				// raises certainty to Yes, which would wrongly upgrade Maybe-defined
				// variables — `if (empty($a['bar']))` for instance leaves `$a`
				// Maybe-defined because `empty()` tolerates undefined offsets.
				if (!$scope->hasExpressionType($targetExpr)->yes()) {
					continue;
				}
				if (!$leftTruthyScope->hasExpressionType($targetExpr)->yes()) {
					continue;
				}
				if (!$rightTruthyScope->hasExpressionType($targetExpr)->yes()) {
					continue;
				}

				$origType = $scope->getType($targetExpr);

				$leftType = $leftTruthyScope->getType($targetExpr);
				$leftNarrowed = !$leftType->equals($origType) && $origType->isSuperTypeOf($leftType)->yes();
				if (!$leftNarrowed) {
					continue;
				}

				$rightType = $rightTruthyScope->getType($targetExpr);
				$rightNarrowed = !$rightType->equals($origType) && $origType->isSuperTypeOf($rightType)->yes();
				if (!$rightNarrowed) {
					continue;
				}

				$unionType = TypeCombinator::union($leftType, $rightType);
				if ($unionType->equals($origType)) {
					continue;
				}

				$types = $types->unionWith(
					$typeSpecifier->create($targetExpr, $unionType, TypeSpecifierContext::createTrue(), $scope),
				);
			}
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
