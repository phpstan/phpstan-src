<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PHPStan\Analyser\DisjunctionHolderProjectionAugment;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Type;
use function array_filter;
use function array_keys;
use function array_values;
use function is_string;

/**
 * The conjunction narrowing - BooleanAnd's specify semantics - composed from
 * per-operand narrowing closures and branch scopes instead of the operands'
 * ExpressionResults, so conjunctions without an AST node (the falsy fold of
 * a multi-subject isset()) reuse it without synthesizing BooleanAnd chains.
 */
#[AutowiredService]
final class BooleanNarrowingHelper
{

	public function __construct(
		private ConditionalExpressionHolderHelper $conditionalExpressionHolderHelper,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	/**
	 * The branch scopes are thunks: for a deep boolean chain, eagerly deriving
	 * every level's opposite-polarity scope is quadratic - they resolve only
	 * when a consumer (an augment with candidates, a holder re-derivation)
	 * genuinely needs the state.
	 *
	 * @param callable(MutatingScope, TypeSpecifierContext): SpecifiedTypes $leftTypesCallback
	 * @param callable(MutatingScope, TypeSpecifierContext): SpecifiedTypes $rightTypesCallback
	 * @param callable(): MutatingScope $leftTruthyScope
	 * @param callable(): MutatingScope $leftFalseyScope
	 * @param callable(): MutatingScope $rightFalseyScope
	 */
	public function specifyConjunction(
		NodeScopeResolver $nodeScopeResolver,
		MutatingScope $s,
		TypeSpecifierContext $context,
		Expr $rootExpr,
		Expr $leftExpr,
		callable $leftTypesCallback,
		callable $leftTruthyScope,
		callable $leftFalseyScope,
		Expr $rightExpr,
		callable $rightTypesCallback,
		callable $rightFalseyScope,
	): SpecifiedTypes
	{
			$leftTypes = $leftTypesCallback($s, $context)->setRootExpr($rootExpr);
			// the right operand lives after the left is known true - its narrowing
			// bases read from the left-truthy view, never the raw ask scope
			$rightScope = $leftTruthyScope();
			$rightTypes = $rightTypesCallback($rightScope, $context)->setRootExpr($rootExpr);
		if ($context->true()) {
			$types = $leftTypes->unionWith($rightTypes);
		} else {
			$types = $leftTypes->intersectWith($rightTypes);
			$branchUnionAugment = $this->conditionalExpressionHolderHelper->buildBranchUnionAugment($nodeScopeResolver, $leftTypes, $rightTypes, $leftFalseyScope, $rightFalseyScope, $types);
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
					$leftHolderTypes = $leftTypesCallback($s, TypeSpecifierContext::createFalsey())->setRootExpr($rootExpr);
				}
				if ($rightHolderTypes->getSureTypes() === [] && $rightHolderTypes->getSureNotTypes() === []) {
					$rightHolderTypes = $rightTypesCallback($rightScope, TypeSpecifierContext::createFalsey())->setRootExpr($rootExpr);
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
			if ($leftCondTypes->getSureTypes() === [] && $leftCondTypes->getSureNotTypes() === [] && $this->truthinessImpliedByTruthyNarrowing($leftExpr)) {
				$truthyLeftTypes = $leftTypesCallback($s, TypeSpecifierContext::createTruthy());
				if ($this->allExpressionsTrackable($truthyLeftTypes)) {
					$leftCondTypes = new SpecifiedTypes($truthyLeftTypes->getSureNotTypes(), $truthyLeftTypes->getSureTypes());
				}
			}
			if ($rightCondTypes->getSureTypes() === [] && $rightCondTypes->getSureNotTypes() === [] && $this->truthinessImpliedByTruthyNarrowing($rightExpr)) {
				$truthyRightTypes = $rightTypesCallback($rightScope, TypeSpecifierContext::createTruthy());
				if ($this->allExpressionsTrackable($truthyRightTypes)) {
					$rightCondTypes = new SpecifiedTypes($truthyRightTypes->getSureNotTypes(), $truthyRightTypes->getSureTypes());
				}
			}
			$result = $types->withoutConditionalExpressionHolders();
			$recipes = [
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($s, $leftCondTypes, $rightHolderTypes, false, true, $rightScope, $rightExpr),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($s, $rightCondTypes, $leftHolderTypes, false, true, null, $leftExpr),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($s, $leftCondTypes, $rightHolderTypes, true, true, $rightScope, $rightExpr),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($s, $rightCondTypes, $leftHolderTypes, true, true, null, $leftExpr),
			];
			return $result->setConditionalExpressionHolderRecipes(array_values(array_filter($recipes)))->setRootExpr($rootExpr);
		}

			return $types;
	}

	/**
	 * The disjunction narrowing - BooleanOr's specify semantics - composed
	 * from per-operand narrowing/type closures and branch scopes instead of
	 * the operands' ExpressionResults, so disjunctions without an AST node
	 * (the non-null narrowing of empty()) reuse it without synthesizing
	 * BooleanOr chains.
	 *
	 * The operand verdict callbacks take only the asked flavour: the decided
	 * checks read the operands' walk-position types (the results' own
	 * evaluation points), never the asking scope.
	 *
	 * @param callable(MutatingScope, TypeSpecifierContext): SpecifiedTypes $leftTypesCallback
	 * @param callable(bool): Type $leftTypeCallback
	 * @param callable(MutatingScope, TypeSpecifierContext): SpecifiedTypes $rightTypesCallback
	 * @param callable(bool): Type $rightTypeCallback
	 * @param callable(): MutatingScope $leftTruthyScope
	 * @param callable(): MutatingScope $leftFalseyScope
	 * @param callable(): MutatingScope $rightTruthyScope
	 */
	public function specifyDisjunction(
		NodeScopeResolver $nodeScopeResolver,
		MutatingScope $s,
		TypeSpecifierContext $context,
		Expr $rootExpr,
		Expr $leftExpr,
		callable $leftTypesCallback,
		callable $leftTypeCallback,
		callable $leftTruthyScope,
		callable $leftFalseyScope,
		Expr $rightExpr,
		callable $rightTypesCallback,
		callable $rightTypeCallback,
		callable $rightTruthyScope,
	): SpecifiedTypes
	{
			$leftTypes = $leftTypesCallback($s, $context)->setRootExpr($rootExpr);
			$rightScope = $leftFalseyScope();
			$rightTypes = $rightTypesCallback($rightScope, $context)->setRootExpr($rootExpr);

		if ($context->true()) {
			if (
				$leftTypeCallback($s->nativeTypesPromoted)->toBoolean()->isFalse()->yes()
			) {
				$types = $rightTypes;
			} elseif (
				$leftTypeCallback($s->nativeTypesPromoted)->toBoolean()->isTrue()->yes()
				|| $rightTypeCallback($s->nativeTypesPromoted)->toBoolean()->isFalse()->yes()
			) {
				$types = $leftTypes;
			} else {
				$types = $leftTypes->intersectWith($rightTypes);
				$alternativeKeys = [];
				foreach (array_keys($types->getAlternativeTypes()) as $exprString) {
					$alternativeKeys[$exprString] = true;
				}
				$types = $types->withDeferredAugment(new DisjunctionHolderProjectionAugment(
					$nodeScopeResolver,
					$this->defaultNarrowingHelper,
					$leftTruthyScope,
					$rightScope,
					$rightTruthyScope,
					$alternativeKeys,
				));
				$branchUnionAugment = $this->conditionalExpressionHolderHelper->buildBranchUnionAugment($nodeScopeResolver, $leftTypes, $rightTypes, $leftTruthyScope, $rightTruthyScope, $types);
				if ($branchUnionAugment !== null) {
					$types = $types->withDeferredAugment($branchUnionAugment);
				}
			}
		} else {
			$types = $leftTypes->unionWith($rightTypes);
		}

		if ($context->true()) {
			$result = $types->withoutConditionalExpressionHolders();
			$recipes = [
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($s, $leftTypes, $rightTypes, false, false, $rightScope, $rightExpr),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($s, $rightTypes, $leftTypes, false, false, null, $leftExpr),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($s, $leftTypes, $rightTypes, true, false, $rightScope, $rightExpr),
				$this->conditionalExpressionHolderHelper->buildConditionalHolderRecipe($s, $rightTypes, $leftTypes, true, false, null, $leftExpr),
			];
			return $result->setConditionalExpressionHolderRecipes(array_values(array_filter($recipes)))->setRootExpr($rootExpr);
		}

			return $types;
	}

	/**
	 * Whether the side's truthy narrowing is EQUIVALENT to the side being
	 * true - the requirement for using its inversion as a holder antecedent.
	 * isset() qualifies: it is exactly the offset's non-nullness. Anything
	 * else reaching the antecedent-swap fallback (e.g. a non-strict
	 * in_array() call, whose truthy narrowing only implies a non-empty
	 * haystack) must not stand in for its own truth.
	 */
	private function truthinessImpliedByTruthyNarrowing(Expr $side): bool
	{
		return $side instanceof Expr\Isset_;
	}

	private function allExpressionsTrackable(SpecifiedTypes $types): bool
	{
		// an alternative-form entry has no single condition type to track
		if ($types->getAlternativeTypes() !== []) {
			return false;
		}

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

}
