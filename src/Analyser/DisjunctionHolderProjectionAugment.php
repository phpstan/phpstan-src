<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\TypeCombinator;
use function array_key_first;

/**
 * The disjunction-truthy projection of conditional-holder narrowings: an
 * expression that registered conditional holders (on the applying scope or on
 * the left-falsey walk scope) and that both operands' truthy scopes narrow -
 * through those holders firing - is narrowed to the union of its branch
 * types. Candidate discovery and the does-it-actually-narrow gates run
 * against the applying scope; the branch reads use the operand-walk truthy
 * scopes captured at compose time.
 */
final class DisjunctionHolderProjectionAugment implements DeferredSpecifiedTypesAugment
{

	/**
	 * The operand truthy scopes are thunks resolved only when a candidate
	 * passes the applying-scope gates - deriving them per level of a deep
	 * boolean chain is quadratic.
	 *
	 * @param callable(): MutatingScope $leftTruthyScope
	 * @param callable(): MutatingScope $rightTruthyScope
	 * @param array<string, true> $alternativeKeys expressions the exact either-branch
	 *        merge already constrains - the weaker branch-scope union must not
	 *        be added on top
	 */
	public function __construct(
		private TypeSpecifier $typeSpecifier,
		private $leftTruthyScope,
		private MutatingScope $leftFalseyScope,
		private $rightTruthyScope,
		private array $alternativeKeys,
	)
	{
	}

	public function evaluate(MutatingScope $scope): ?SpecifiedTypes
	{
		$result = null;
		$seen = [];
		$leftTruthyScope = null;
		$rightTruthyScope = null;
		foreach ([$scope, $this->leftFalseyScope] as $sourceScope) {
			foreach ($sourceScope->getConditionalExpressions() as $rootExprString => $holders) {
				if (isset($seen[$rootExprString])) {
					continue;
				}
				if ($holders === []) {
					continue;
				}
				$seen[$rootExprString] = true;
				$targetExpr = $holders[array_key_first($holders)]->getTypeHolder()->getExpr();

				if (isset($this->alternativeKeys[$rootExprString])) {
					continue;
				}

				// Only project when the target stays Yes-defined in the original
				// scope and in both filtered branches. A sure type implicitly
				// raises certainty to Yes, which would wrongly upgrade Maybe-defined
				// variables — `if (empty($a['bar']))` for instance leaves `$a`
				// Maybe-defined because `empty()` tolerates undefined offsets.
				if (!$scope->hasExpressionType($targetExpr)->yes()) {
					continue;
				}
				$leftTruthyScope ??= ($this->leftTruthyScope)();
				$rightTruthyScope ??= ($this->rightTruthyScope)();

				// the guard above pins the target as tracked on the applying
				// scope; the branch scopes are its own filtered views, so their
				// reads answer from state (or price the same tracked state)
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

				$created = $this->typeSpecifier->create($targetExpr, $unionType, TypeSpecifierContext::createTrue(), $scope);
				$result = $result === null ? $created : $result->unionWith($created);
			}
		}

		return $result;
	}

}
