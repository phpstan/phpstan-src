<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;

/**
 * A deferred description of the boolean-decomposition conditional holders
 * (`&&` asserted false, `||` asserted true): the raw narrowing entries of the
 * condition side and the holder side, captured where the boolean narrowing was
 * composed. The state-dependent math - the condition complements against the
 * current type, the holder target types, the vacuity checks - runs in
 * evaluate() against the scope the narrowing is applied to
 * (MutatingScope::filterBySpecifiedTypes()), never the scope the composition ran
 * on.
 */
final class ConditionalExpressionHolderRecipe
{

	/**
	 * @param list<array{string, Expr, bool, Type}> $conditionEntries [exprString, expr, fromSureTypes, type]
	 * @param list<array{string, Expr, Type, ?Type}> $holderEntries [exprString, expr, type, target type pinned at compose time (null = read the applying scope)]
	 */
	public function __construct(
		private array $conditionEntries,
		private array $holderEntries,
		private bool $holdersFromSureTypes,
	)
	{
	}

	/**
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	public function evaluate(MutatingScope $scope): array
	{
		$conditionExpressionTypes = [];
		$droppedNoOpConditions = [];
		// the unnarrowed type of each condition expression, for the
		// dropped-self-condition complement below
		$conditionOriginalTypes = [];
		foreach ($this->conditionEntries as [$exprString, $expr, $fromSureTypes, $type]) {
			$scopeType = $scope->getType($expr);
			$conditionType = $fromSureTypes
				? TypeCombinator::remove($scopeType, $type)
				: TypeCombinator::intersect($scopeType, $type);
			if ($scopeType->equals($conditionType)) {
				$droppedNoOpConditions[$exprString] = true;
				continue;
			}

			$conditionExpressionTypes[$exprString] = ExpressionTypeHolder::createYes($expr, $conditionType);
			$conditionOriginalTypes[$exprString] = $scopeType;
		}

		if ($conditionExpressionTypes === []) {
			return [];
		}

		$holders = [];
		foreach ($this->holderEntries as [$exprString, $expr, $type, $pinnedTargetType]) {
			// The target's only link to the antecedent was a no-op relation (e.g.
			// `$a === $b`) that got dropped, so the antecedent no longer constrains
			// it. Projecting a consequent onto it would fire unsoundly. Skip it.
			if (array_key_exists($exprString, $droppedNoOpConditions)) {
				continue;
			}

			$conditions = $conditionExpressionTypes;
			$droppedSelfCondition = null;
			if (isset($conditions[$exprString])) {
				$droppedSelfCondition = $conditions[$exprString];
				unset($conditions[$exprString]);
			}

			if ($conditions === []) {
				continue;
			}

			$targetType = $pinnedTargetType ?? $scope->getType($expr);
			$holderType = $this->holdersFromSureTypes
				? TypeCombinator::intersect($targetType, $type)
				: TypeCombinator::remove($targetType, $type);

			// The dropped self-condition narrowed the target; without it the
			// holder must allow the values it excluded, or it over-narrows when
			// only the remaining conditions hold. So union back the complement.
			if ($droppedSelfCondition !== null) {
				$complement = TypeCombinator::remove($conditionOriginalTypes[$exprString], $droppedSelfCondition->getType());
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

}
