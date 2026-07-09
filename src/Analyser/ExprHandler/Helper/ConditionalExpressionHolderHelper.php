<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PHPStan\Analyser\ConditionalExpressionHolder;
use PHPStan\Analyser\ExpressionTypeHolder;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function count;
use function is_string;

/**
 * Builds the conditional expression holders used to project narrowings of
 * boolean operands (`&&`, `||`) into later scopes. Shared by BooleanAndHandler
 * and BooleanOrHandler.
 */
#[AutowiredService]
final class ConditionalExpressionHolderHelper
{

	public function __construct(
		private TypeSpecifier $typeSpecifier,
	)
	{
	}

	/**
	 * Pins the boolean value of a whole `&&`/`||` condition (`true` in the
	 * truthy branch, `false` in the falsey branch) into a scope and runs the
	 * conditional-expression resolution against it, without re-narrowing the
	 * operands.
	 *
	 * The single-variable case (`if ($cond) { $x = 1; } if ($cond) { echo $x; }`)
	 * already works because the narrowed variable is stored in the scope and
	 * becomes a guard when the branches merge. A compound condition narrows no
	 * single variable to a definite value, so nothing is left to guard on.
	 * Pinning the condition expression's boolean value gives the merge a guard
	 * keyed on the whole condition, and re-running the resolution lets a later
	 * identical `if` re-derive the same narrowing — all while preserving any
	 * side effects (e.g. assignments) already baked into the operand-narrowed
	 * scope.
	 */
	public function specifyWholeExpressionType(MutatingScope $scope, Expr $expr, bool $value): MutatingScope
	{
		$specifiedTypes = $this->typeSpecifier->create(
			$expr,
			new ConstantBooleanType($value),
			TypeSpecifierContext::createTrue(),
			$scope,
		)->setRootExpr($expr);

		return $scope->filterBySpecifiedTypes($specifiedTypes);
	}

	public function augmentDisjunctionTypes(
		MutatingScope $scope,
		MutatingScope $rightScope,
		SpecifiedTypes $leftNormalized,
		SpecifiedTypes $rightNormalized,
		Expr $leftExpr,
		Expr $rightExpr,
		bool $truthy,
		SpecifiedTypes $types,
	): SpecifiedTypes
	{
		$candidateExprs = [];
		foreach ($leftNormalized->getSureTypes() as $exprString => [$exprNode, $type]) {
			$candidateExprs[$exprString] = $exprNode;
		}
		foreach ($rightNormalized->getSureTypes() as $exprString => [$exprNode, $type]) {
			$candidateExprs[$exprString] = $exprNode;
		}

		$existingSureTypes = $types->getSureTypes();

		$viableCandidates = [];
		foreach ($candidateExprs as $exprString => $targetExpr) {
			if (isset($existingSureTypes[$exprString])) {
				continue;
			}
			if (!$scope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}
			$viableCandidates[$exprString] = $targetExpr;
		}

		if ($viableCandidates === []) {
			return $types;
		}

		if ($truthy) {
			$leftFilteredScope = $scope->filterByTruthyValue($leftExpr);
			$rightFilteredScope = $rightScope->filterByTruthyValue($rightExpr);
		} else {
			$leftFilteredScope = $scope->filterByFalseyValue($leftExpr);
			$rightFilteredScope = $rightScope->filterByFalseyValue($rightExpr);
		}

		foreach ($viableCandidates as $targetExpr) {
			if (!$leftFilteredScope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}
			if (!$rightFilteredScope->hasExpressionType($targetExpr)->yes()) {
				continue;
			}

			$originalType = $scope->getType($targetExpr);
			$leftType = $leftFilteredScope->getType($targetExpr);
			$rightType = $rightFilteredScope->getType($targetExpr);

			if ($leftType->equals($originalType) || !$originalType->isSuperTypeOf($leftType)->yes()) {
				continue;
			}

			if ($rightType->equals($originalType) || !$originalType->isSuperTypeOf($rightType)->yes()) {
				continue;
			}

			$unionType = TypeCombinator::union($leftType, $rightType);
			if ($unionType->equals($originalType)) {
				continue;
			}

			$types = $types->unionWith(
				$this->typeSpecifier->create($targetExpr, $unionType, TypeSpecifierContext::createTrue(), $scope),
			);
		}

		return $types;
	}

	/**
	 * Combines several `processBooleanConditionalTypes()` results into one map.
	 *
	 * A plain `array_merge()` would be keyed by the target expression string and
	 * therefore let a later result overwrite an earlier one targeting the same
	 * expression, silently dropping a holder. Holders for the same expression are
	 * unioned by their key instead so all of them survive.
	 *
	 * @param list<array<string, ConditionalExpressionHolder[]>> $holderLists
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	public function mergeConditionalHolders(array $holderLists): array
	{
		$result = [];
		foreach ($holderLists as $holders) {
			foreach ($holders as $exprString => $exprHolders) {
				foreach ($exprHolders as $key => $holder) {
					$result[$exprString][$key] = $holder;
				}
			}
		}

		return $result;
	}

	/**
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	public function processBooleanConditionalTypes(Scope $scope, SpecifiedTypes $conditionSpecifiedTypes, SpecifiedTypes $holderSpecifiedTypes, bool $holdersFromSureTypes, bool $holderSideIsNegated, Scope $rightScope, ?Expr $holderSideExpr = null): array
	{
		// The condition side asserts that its sub-expression evaluates truthy.
		// When that sub-expression is itself a compound boolean (e.g. `$a && $b`),
		// the narrowings making it true are spread across both the sure and
		// sureNot lists of its specification. All of them are conjuncts of the
		// single "this side is true" condition, so they must be gathered together
		// into one condition set. Picking only one list would drop a conjunct and
		// let the resulting holder fire too eagerly.
		$conditionExpressionTypes = [];
		$droppedNoOpConditions = [];
		foreach ($conditionSpecifiedTypes->getSureTypes() as $exprString => [$expr, $type]) {
			if (!$this->isTrackableExpression($expr)) {
				continue;
			}

			$scopeType = $scope->getType($expr);
			$conditionType = TypeCombinator::remove($scopeType, $type);
			if ($scopeType->equals($conditionType)) {
				$droppedNoOpConditions[$exprString] = true;
				continue;
			}

			$conditionExpressionTypes[$exprString] = ExpressionTypeHolder::createYes(
				$expr,
				$conditionType,
			);
		}
		foreach ($conditionSpecifiedTypes->getSureNotTypes() as $exprString => [$expr, $type]) {
			if (!$this->isTrackableExpression($expr)) {
				continue;
			}

			$scopeType = $scope->getType($expr);
			$conditionType = TypeCombinator::intersect($scopeType, $type);
			if ($scopeType->equals($conditionType)) {
				$droppedNoOpConditions[$exprString] = true;
				continue;
			}

			$conditionExpressionTypes[$exprString] = ExpressionTypeHolder::createYes(
				$expr,
				$conditionType,
			);
		}

		if (count($conditionExpressionTypes) > 0) {
			$holders = [];
			$holderTypes = $holdersFromSureTypes ? $holderSpecifiedTypes->getSureTypes() : $holderSpecifiedTypes->getSureNotTypes();

			// A holder side that is itself a compound boolean cannot always be split
			// into independent per-expression holders. In the `BooleanAnd` false
			// context the holder asserts its side is false: when that side is a
			// conjunction (`$a && $b`), its negation is the disjunction `!$a || !$b`,
			// which has no per-expression narrowing — narrowing each conjunct
			// independently would drop a reachable value (e.g. `$a = false, $b = true`).
			// Symmetrically, in the `BooleanOr` true context the holder asserts its
			// side is true, and a disjunction side (`$a || $b`) is itself a disjunction.
			// Such a side is left whole rather than split into over-narrowing holders.
			if ($this->isUnsplittableCompoundHolderSide($holderSideExpr, $holderSideIsNegated)) {
				return [];
			}

			foreach ($holderTypes as $exprString => [$expr, $type]) {
				if (!$this->isTrackableExpression($expr)) {
					continue;
				}

				// The target's only link to the antecedent was a no-op relation (e.g.
				// `$a === $b`) that got dropped, so the antecedent no longer constrains
				// it. Projecting a consequent onto it would fire unsoundly. Skip it.
				if (array_key_exists($exprString, $droppedNoOpConditions)) {
					continue;
				}

				$conditions = $conditionExpressionTypes;
				$droppedSelfCondition = null;
				foreach ($conditions as $conditionExprString => $condition) {
					if ($conditionExprString !== $exprString) {
						continue;
					}
					$droppedSelfCondition = $condition;
					unset($conditions[$conditionExprString]);
				}

				if (count($conditions) === 0) {
					continue;
				}

				$targetScope = $expr instanceof Expr\Variable ? $scope : $rightScope;
				$targetType = $targetScope->getType($expr);
				$holderType = $holdersFromSureTypes
					? TypeCombinator::intersect($targetType, $type)
					: TypeCombinator::remove($targetType, $type);

				// The dropped self-condition narrowed the target; without it the
				// holder must allow the values it excluded, or it over-narrows when
				// only the remaining conditions hold. So union back the complement.
				if ($droppedSelfCondition !== null) {
					$complement = TypeCombinator::remove($scope->getType($expr), $droppedSelfCondition->getType());
					if (!$complement instanceof NeverType) {
						$holderType = TypeCombinator::union($holderType, $complement);
					}
				}

				// These boolean-decomposition holders only refine an expression's
				// type in a future scope; they must never collapse it to never and
				// thereby mark the whole scope unreachable. A never result is an
				// artifact (e.g. removing a non-nullable property's full type after
				// swapping isset() narrowing), not a real contradiction.
				if ($holderType instanceof NeverType && !$targetType instanceof NeverType) {
					continue;
				}
				$holder = new ConditionalExpressionHolder(
					$conditions,
					ExpressionTypeHolder::createYes($expr, $holderType),
				);
				$holders[$exprString] ??= [];
				$holders[$exprString][$holder->getKey()] = $holder;
			}

			return $holders;
		}

		return [];
	}

	/**
	 * A holder side whose truth value is asserted as a disjunction cannot be
	 * decomposed into independent per-expression holders. That happens for a
	 * conjunction (`&&`) asserted false (negated context) and for a disjunction
	 * (`||`) asserted true.
	 */
	private function isUnsplittableCompoundHolderSide(?Expr $holderSideExpr, bool $holderSideIsNegated): bool
	{
		if ($holderSideExpr === null) {
			return false;
		}

		if ($holderSideIsNegated) {
			return $holderSideExpr instanceof BooleanAnd || $holderSideExpr instanceof LogicalAnd;
		}

		return $holderSideExpr instanceof BooleanOr || $holderSideExpr instanceof LogicalOr;
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

}
