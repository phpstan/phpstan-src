<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Closure;
use PhpParser\Node\Expr;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_merge;
use function count;

final class SpecifiedTypes
{

	/** @var (Closure(TypeSpecifierContext, bool): self)|null */
	private static ?Closure $emptySpecifyCallback = null;

	/**
	 * Cross-producing alternative forms doubles the term count per conjunction;
	 * past this many terms the entry is widened to a single covering term.
	 */
	private const ALTERNATIVE_TERMS_LIMIT = 32;

	private bool $overwrite = false;

	/** @var array<string, ConditionalExpressionHolder[]> */
	private array $newConditionalExpressionHolders = [];

	/**
	 * Deferred boolean-decomposition holders, evaluated against the applying
	 * scope by MutatingScope::applySpecifiedTypes().
	 *
	 * @var list<ConditionalExpressionHolderRecipe>
	 */
	private array $conditionalExpressionHolderRecipes = [];

	/**
	 * State-dependent augmentations evaluated against the applying scope by
	 * MutatingScope::applySpecifiedTypes(); their entries join the applied
	 * batch.
	 *
	 * @var list<DeferredSpecifiedTypesAugment>
	 */
	private array $deferredAugments = [];

	private ?Expr $rootExpr = null;

	/**
	 * Alternative-form entries produced by intersectWith() when the two sides
	 * constrain the same expression with different kinds (a sure type in one
	 * branch, a sure-not in the other). Each term (sure, subtract) reads as
	 * `(sure ?? current type) minus subtract`; the entry's value is the union
	 * of its terms, evaluated by MutatingScope::applySpecifiedTypes() against
	 * the subject's type at the application point - the deferred form of what
	 * the old SpecifiedTypes::normalize() computed eagerly with a scope.
	 *
	 * @var array<string, array{Expr, list<array{?Type, ?Type}>}>
	 */
	private array $alternativeTypes = [];

	/**
	 * @api
	 * @param array<string, array{Expr, Type}> $sureTypes
	 * @param array<string, array{Expr, Type}> $sureNotTypes
	 */
	public function __construct(
		private array $sureTypes = [],
		private array $sureNotTypes = [],
	)
	{
	}

	/**
	 * A shared no-narrowing specify callback for results whose expression never
	 * narrows anything (literals, virtual write nodes) - one process-wide
	 * closure instead of one allocation per created ExpressionResult.
	 *
	 * @return Closure(TypeSpecifierContext, bool): self
	 */
	public static function emptySpecifyCallback(): Closure
	{
		return self::$emptySpecifyCallback ??= static fn (): self => new self();
	}

	/**
	 * Normally, $sureTypes in truthy context are used to intersect with the pre-existing type.
	 * And $sureNotTypes are used to remove type from the pre-existing type.
	 *
	 * Example: By default, non-empty-string intersected with '' (ConstantStringType) will lead to NeverType.
	 * Because it's not possible to narrow non-empty-string to an empty string.
	 *
	 * In rare cases, a type-specifying extension might want to overwrite the pre-existing types
	 * without taking the pre-existing types into consideration.
	 *
	 * In that case it should also call setAlwaysOverwriteTypes() on
	 * the returned object.
	 *
	 * ! Only do this if you're certain. Otherwise, this is a source of common bugs. !
	 *
	 * @api
	 */
	public function setAlwaysOverwriteTypes(): self
	{
		$self = clone $this;
		$self->overwrite = true;

		return $self;
	}

	/**
	 * @api
	 */
	public function setRootExpr(?Expr $rootExpr): self
	{
		$self = clone $this;
		$self->rootExpr = $rootExpr;

		return $self;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $newConditionalExpressionHolders
	 */
	public function setNewConditionalExpressionHolders(array $newConditionalExpressionHolders): self
	{
		$self = clone $this;
		$self->newConditionalExpressionHolders = $newConditionalExpressionHolders;

		return $self;
	}

	/**
	 * @param list<ConditionalExpressionHolderRecipe> $recipes
	 */
	public function setConditionalExpressionHolderRecipes(array $recipes): self
	{
		$self = clone $this;
		$self->conditionalExpressionHolderRecipes = $recipes;

		return $self;
	}

	/**
	 * @return list<ConditionalExpressionHolderRecipe>
	 */
	public function getConditionalExpressionHolderRecipes(): array
	{
		return $this->conditionalExpressionHolderRecipes;
	}

	public function withDeferredAugment(DeferredSpecifiedTypesAugment $augment): self
	{
		$self = clone $this;
		$self->deferredAugments = [...$this->deferredAugments, $augment];

		return $self;
	}

	/**
	 * @return list<DeferredSpecifiedTypesAugment>
	 */
	public function getDeferredAugments(): array
	{
		return $this->deferredAugments;
	}

	/**
	 * @api
	 * @return array<string, array{Expr, Type}>
	 */
	public function getSureTypes(): array
	{
		return $this->sureTypes;
	}

	/**
	 * @api
	 * @return array<string, array{Expr, Type}>
	 */
	public function getSureNotTypes(): array
	{
		return $this->sureNotTypes;
	}

	/**
	 * @return array<string, array{Expr, list<array{?Type, ?Type}>}>
	 */
	public function getAlternativeTypes(): array
	{
		return $this->alternativeTypes;
	}

	/**
	 * A copy without conditional-expression holders and holder recipes - for
	 * the boolean-decomposition tails that replace them with freshly built
	 * recipes while keeping everything else (entries, alternatives, augments)
	 * intact.
	 */
	public function withoutConditionalExpressionHolders(): self
	{
		$self = clone $this;
		$self->newConditionalExpressionHolders = [];
		$self->conditionalExpressionHolderRecipes = [];

		return $self;
	}

	public function shouldOverwrite(): bool
	{
		return $this->overwrite;
	}

	/**
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	public function getNewConditionalExpressionHolders(): array
	{
		return $this->newConditionalExpressionHolders;
	}

	public function getRootExpr(): ?Expr
	{
		return $this->rootExpr;
	}

	public function removeExpr(string $exprString): self
	{
		$self = clone $this;
		unset($self->sureTypes[$exprString]);
		unset($self->sureNotTypes[$exprString]);
		unset($self->alternativeTypes[$exprString]);

		return $self;
	}

	/**
	 * The either-branch merge: the result holds when at least one side holds
	 * (the falsey narrowing of `&&`, the truthy narrowing of `||`). Same-kind
	 * constraints merge exactly (sure: union of values, sure-not: intersection
	 * of removed types); an expression constrained with different kinds on the
	 * two sides becomes an alternative-form entry - `(sure ?? current) minus
	 * subtract` per side, united at the application point. An expression
	 * constrained on only one side is unconstrained in the merge.
	 *
	 * @api
	 */
	public function intersectWith(SpecifiedTypes $other): self
	{
		$sureTypeUnion = [];
		$sureNotTypeUnion = [];
		$alternativeUnion = [];
		$rootExpr = $this->mergeRootExpr($this->rootExpr, $other->rootExpr);

		$keys = [];
		foreach ([$this->sureTypes, $this->sureNotTypes, $this->alternativeTypes, $other->sureTypes, $other->sureNotTypes, $other->alternativeTypes] as $map) {
			foreach ($map as $exprString => $entry) {
				$keys[$exprString] = $entry[0];
			}
		}

		foreach ($keys as $exprString => $exprNode) {
			$thisTerms = $this->collectTerms($exprString);
			$otherTerms = $other->collectTerms($exprString);
			if ($thisTerms === null || $otherTerms === null) {
				// unconstrained on one side - unconstrained in the merge
				continue;
			}

			$terms = array_merge($thisTerms, $otherTerms);
			$sures = [];
			$subtracts = [];
			$pureSure = true;
			$pureSureNot = true;
			foreach ($terms as [$sure, $subtract]) {
				if ($sure === null) {
					$pureSure = false;
				} else {
					$sures[] = $sure;
				}
				if ($subtract === null) {
					$pureSureNot = false;
				} else {
					$subtracts[] = $subtract;
				}
				if ($sure === null || $subtract === null) {
					continue;
				}

				$pureSure = false;
				$pureSureNot = false;
			}

			if ($pureSure) {
				$sureTypeUnion[$exprString] = [$exprNode, TypeCombinator::union(...$sures)];
			} elseif ($pureSureNot) {
				$merged = TypeCombinator::intersect(...$subtracts);
				if ($merged instanceof NeverType) {
					// removing never removes nothing - a vacuous constraint
					continue;
				}
				$sureNotTypeUnion[$exprString] = [$exprNode, $merged];
			} else {
				$alternativeUnion[$exprString] = [$exprNode, $terms];
			}
		}

		$result = new self($sureTypeUnion, $sureNotTypeUnion);
		$result->alternativeTypes = $alternativeUnion;
		if ($this->overwrite && $other->overwrite) {
			$result = $result->setAlwaysOverwriteTypes();
		}

		return $result->setRootExpr($rootExpr);
	}

	/**
	 * This side's constraint on the expression as alternative-form terms, or
	 * null when unconstrained. A sure and a sure-not on the same key are one
	 * term (the sure with the sure-not removed) - both constraints hold here.
	 *
	 * @return list<array{?Type, ?Type}>|null
	 */
	private function collectTerms(string|int $exprString): ?array
	{
		if (isset($this->alternativeTypes[$exprString])) {
			$terms = $this->alternativeTypes[$exprString][1];
			// sure/sureNot on the same key as an alternative entry: fold them
			// into every term (they hold in addition to the alternatives)
			if (isset($this->sureTypes[$exprString]) || isset($this->sureNotTypes[$exprString])) {
				$extraSure = $this->sureTypes[$exprString][1] ?? null;
				$extraSubtract = $this->sureNotTypes[$exprString][1] ?? null;
				$folded = [];
				foreach ($terms as [$sure, $subtract]) {
					if ($extraSure !== null) {
						$sure = $sure === null ? $extraSure : TypeCombinator::intersect($sure, $extraSure);
					}
					if ($extraSubtract !== null) {
						$subtract = $subtract === null ? $extraSubtract : TypeCombinator::union($subtract, $extraSubtract);
					}
					$folded[] = [$sure, $subtract];
				}

				return $folded;
			}

			return $terms;
		}

		$sure = $this->sureTypes[$exprString][1] ?? null;
		$subtract = $this->sureNotTypes[$exprString][1] ?? null;
		if ($sure === null && $subtract === null) {
			return null;
		}

		return [[$sure, $subtract]];
	}

	/**
	 * The both-sides-hold merge of two alternative forms. An entry's value is
	 * the union of its terms, so conjoining two entries distributes over both
	 * lists: every pair of terms contributes `(sureA and sureB) minus (subtractA
	 * or subtractB)`, the same folding collectTerms() does for a sure/sure-not
	 * pair. Pairs whose sure types cannot hold together drop out.
	 *
	 * @param list<array{?Type, ?Type}> $terms
	 * @param list<array{?Type, ?Type}> $otherTerms
	 * @return list<array{?Type, ?Type}>
	 */
	private static function conjoinTerms(array $terms, array $otherTerms): array
	{
		$conjoined = [];
		foreach ($terms as [$sure, $subtract]) {
			foreach ($otherTerms as [$otherSure, $otherSubtract]) {
				if ($sure === null) {
					$mergedSure = $otherSure;
				} elseif ($otherSure === null) {
					$mergedSure = $sure;
				} else {
					$mergedSure = TypeCombinator::intersect($sure, $otherSure);
				}

				if ($subtract === null) {
					$mergedSubtract = $otherSubtract;
				} elseif ($otherSubtract === null) {
					$mergedSubtract = $subtract;
				} else {
					$mergedSubtract = TypeCombinator::union($subtract, $otherSubtract);
				}

				if ($mergedSure !== null) {
					if ($mergedSubtract !== null) {
						// a fixed base with a subtraction is just the narrower base -
						// folding it keeps the term list free of redundant pairs
						$mergedSure = TypeCombinator::remove($mergedSure, $mergedSubtract);
						$mergedSubtract = null;
					}
					if ($mergedSure instanceof NeverType) {
						continue;
					}
				}

				$conjoined[] = [$mergedSure, $mergedSubtract];
			}
		}

		if ($conjoined === []) {
			// every pair was impossible - so is the conjunction
			return [[new NeverType(), null]];
		}

		$conjoined = self::dedupeTerms($conjoined);
		if (count($conjoined) > self::ALTERNATIVE_TERMS_LIMIT) {
			return [self::widenTerms($conjoined)];
		}

		return $conjoined;
	}

	/**
	 * @param list<array{?Type, ?Type}> $terms
	 * @return list<array{?Type, ?Type}>
	 */
	private static function dedupeTerms(array $terms): array
	{
		$deduped = [];
		foreach ($terms as [$sure, $subtract]) {
			foreach ($deduped as [$seenSure, $seenSubtract]) {
				if (($sure === null) !== ($seenSure === null)) {
					continue;
				}
				if (($subtract === null) !== ($seenSubtract === null)) {
					continue;
				}
				if ($sure !== null && $seenSure !== null && !$sure->equals($seenSure)) {
					continue;
				}
				if ($subtract !== null && $seenSubtract !== null && !$subtract->equals($seenSubtract)) {
					continue;
				}

				continue 2;
			}

			$deduped[] = [$sure, $subtract];
		}

		return $deduped;
	}

	/**
	 * A single term covering the union of all of them - the safety net that
	 * stops a chain of conjoined alternative forms from growing its
	 * cross-product without bound. Widening a narrowing only loses precision.
	 *
	 * @param non-empty-list<array{?Type, ?Type}> $terms
	 * @return array{?Type, ?Type}
	 */
	private static function widenTerms(array $terms): array
	{
		$sures = [];
		$subtracts = [];
		foreach ($terms as [$sure, $subtract]) {
			if ($sure === null) {
				// null reads as the subject's type at the application point,
				// which every term is narrowed to anyway
				$sures = null;
			} elseif ($sures !== null) {
				$sures[] = $sure;
			}

			if ($subtract === null) {
				$subtracts = null;
			} elseif ($subtracts !== null) {
				$subtracts[] = $subtract;
			}
		}

		return [
			$sures === null ? null : TypeCombinator::union(...$sures),
			$subtracts === null ? null : TypeCombinator::intersect(...$subtracts),
		];
	}

	/** @api */
	public function unionWith(SpecifiedTypes $other): self
	{
		$sureTypeUnion = $this->sureTypes + $other->sureTypes;
		$sureNotTypeUnion = $this->sureNotTypes + $other->sureNotTypes;
		$rootExpr = $this->mergeRootExpr($this->rootExpr, $other->rootExpr);

		foreach ($this->sureTypes as $exprString => [$exprNode, $type]) {
			if (!isset($other->sureTypes[$exprString])) {
				continue;
			}

			$sureTypeUnion[$exprString] = [
				$exprNode,
				TypeCombinator::intersect($type, $other->sureTypes[$exprString][1]),
			];
		}

		foreach ($this->sureNotTypes as $exprString => [$exprNode, $type]) {
			if (!isset($other->sureNotTypes[$exprString])) {
				continue;
			}

			$sureNotTypeUnion[$exprString] = [
				$exprNode,
				TypeCombinator::union($type, $other->sureNotTypes[$exprString][1]),
			];
		}

		$result = new self($sureTypeUnion, $sureNotTypeUnion);
		$alternativeUnion = $this->alternativeTypes;
		foreach ($other->alternativeTypes as $exprString => [$exprNode, $otherTerms]) {
			if (!isset($alternativeUnion[$exprString])) {
				$alternativeUnion[$exprString] = [$exprNode, $otherTerms];
				continue;
			}

			$alternativeUnion[$exprString] = [
				$alternativeUnion[$exprString][0],
				self::conjoinTerms($alternativeUnion[$exprString][1], $otherTerms),
			];
		}

		$result->alternativeTypes = $alternativeUnion;
		if ($this->overwrite || $other->overwrite) {
			$result = $result->setAlwaysOverwriteTypes();
		}

		$conditionalExpressionHolders = $this->newConditionalExpressionHolders;
		foreach ($other->newConditionalExpressionHolders as $exprString => $holders) {
			if (!array_key_exists($exprString, $conditionalExpressionHolders)) {
				$conditionalExpressionHolders[$exprString] = $holders;
			} else {
				$conditionalExpressionHolders[$exprString] = array_merge($conditionalExpressionHolders[$exprString], $holders);
			}
		}
		$result->newConditionalExpressionHolders = $conditionalExpressionHolders;
		$result->conditionalExpressionHolderRecipes = array_merge($this->conditionalExpressionHolderRecipes, $other->conditionalExpressionHolderRecipes);
		$result->deferredAugments = array_merge($this->deferredAugments, $other->deferredAugments);

		return $result->setRootExpr($rootExpr);
	}

	private function mergeRootExpr(?Expr $rootExprA, ?Expr $rootExprB): ?Expr
	{
		if ($rootExprA === $rootExprB) {
			return $rootExprA;
		}

		if ($rootExprA === null || $rootExprB === null) {
			return $rootExprA ?? $rootExprB;
		}

		return null;
	}

}
