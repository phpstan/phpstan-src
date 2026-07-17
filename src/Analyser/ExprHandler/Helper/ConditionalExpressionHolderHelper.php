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
use PHPStan\Node\Printer\ExprPrinter;
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
		private ExprPrinter $exprPrinter,
	)
	{
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
	public function processBooleanConditionalTypes(Scope $scope, SpecifiedTypes $conditionSpecifiedTypes, SpecifiedTypes $holderSpecifiedTypes, bool $holdersFromSureTypes, bool $holderSideIsNegated, Scope $rightScope, ?Expr $holderSideExpr = null, ?Expr $conditionSideExpr = null): array
	{
		// The condition (antecedent) side asserts a truth value for its
		// sub-expression, and the holder guard uses that side's per-expression
		// narrowing as if it were equivalent to that truth value. When the
		// condition side is a compound boolean whose asserted truth value is a
		// disjunction (`$a || $b` asserted true, or `$a && $b` asserted false),
		// the narrowing is only a necessary consequence of the truth value, not a
		// sufficient one — e.g. `($a && $b) || ($c && $d)` being true narrows a
		// shared variable, but that narrowing can hold without the disjunction
		// being true. Using such an under-approximating narrowing as a guard fires
		// the holder unsoundly, so no holder is built. This mirrors the holder-side
		// check below with the opposite polarity.
		if ($this->isUnsplittableCompoundSide($conditionSideExpr, !$holderSideIsNegated)) {
			return [];
		}

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
			if ($this->isUnsplittableCompoundSide($holderSideExpr, $holderSideIsNegated)) {
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

				// The guard (the remaining conditions) must actually identify the
				// asserted truth value of the condition side. For a relational predicate
				// like `in_array($needle, $haystack)`, the truthy narrowing of an argument
				// (`$haystack` becoming non-empty) is only "`$haystack` is truthy" — a
				// necessary but not sufficient consequence — so re-asserting it (e.g.
				// `$haystack !== []`) would fire this holder even though the predicate is
				// false. Skip such an under-approximating guard.
				if (
					$conditionSideExpr !== null
					&& $scope instanceof MutatingScope
					&& !$this->guardIdentifiesConditionSide($scope, $conditions, $conditionSideExpr, $expr, $holderSideIsNegated)
				) {
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
	 * A boolean operand whose asserted truth value is a disjunction cannot be
	 * decomposed into independent per-expression narrowings. That happens for a
	 * conjunction (`&&`) asserted false (its negation is a disjunction) and for a
	 * disjunction (`||`) asserted true. Applies to both the holder (consequent)
	 * side and the condition (antecedent) side.
	 */
	private function isUnsplittableCompoundSide(?Expr $sideExpr, bool $isNegated): bool
	{
		if ($sideExpr === null) {
			return false;
		}

		if ($isNegated) {
			return $sideExpr instanceof BooleanAnd || $sideExpr instanceof LogicalAnd;
		}

		return $sideExpr instanceof BooleanOr || $sideExpr instanceof LogicalOr;
	}

	/**
	 * Decides whether the guard (the conditions of a single holder, after the
	 * self-condition has been dropped) identifies the asserted truth value of the
	 * condition side ($conditionAssertedTrue = true when the holder assumes the
	 * condition side is true, false when it assumes false). The guard is identifying
	 * when any of the following holds:
	 *
	 * 1. a condition narrows its expression beyond plain truthiness/falsiness — such
	 *    a narrowing pins down a real value rather than "the expression is truthy";
	 * 2. the consequent is an offset of a guarded container (the
	 *    isset()/array_key_exists() shape `$data = ... => $data[$key] = ...`), whose
	 *    truth PHPStan cannot always re-derive from the offset value type alone
	 *    (e.g. dynamic keys);
	 * 3. re-applying the guard forces the condition side to its asserted truth value.
	 *
	 * A guard that is only "this expression is truthy/falsy" (e.g. `in_array()`
	 * making its haystack non-empty) satisfies none of these and identifies nothing.
	 *
	 * @param array<string, ExpressionTypeHolder> $conditions
	 */
	private function guardIdentifiesConditionSide(MutatingScope $scope, array $conditions, Expr $conditionSideExpr, Expr $targetExpr, bool $conditionAssertedTrue): bool
	{
		foreach ($conditions as $condition) {
			$accessoryScope = $conditionAssertedTrue
				? $scope->filterByTruthyValue($condition->getExpr())
				: $scope->filterByFalseyValue($condition->getExpr());
			if (!$accessoryScope->getType($condition->getExpr())->equals($condition->getType())) {
				return true;
			}
		}

		if (
			$targetExpr instanceof Expr\ArrayDimFetch
			&& array_key_exists($this->exprPrinter->printExpr($targetExpr->var), $conditions)
		) {
			return true;
		}

		$sureTypes = [];
		foreach ($conditions as $exprString => $condition) {
			$sureTypes[$exprString] = [$condition->getExpr(), $condition->getType()];
		}

		// filterBySpecifiedTypes applies the narrowings container-before-offset and
		// intersects them, so an offset guard (`$data['k'] = mixed~null`) is not
		// clobbered by its container guard (`$data = ...hasOffset('k')`). Applying
		// them naively one by one would drop the offset narrowing.
		$guardScope = $scope->filterBySpecifiedTypes(new SpecifiedTypes($sureTypes, []));
		$conditionSideType = $guardScope->getType($conditionSideExpr)->toBoolean();

		return $conditionAssertedTrue
			? $conditionSideType->isTrue()->yes()
			: $conditionSideType->isFalse()->yes();
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
