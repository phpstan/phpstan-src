<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function array_keys;
use function count;
use function spl_object_id;
use function sprintf;

/**
 * The unresolved template arguments of one function body being walked.
 *
 * Created by NodeScopeResolver for the two-pass walk of a function-like body
 * and made visible to producers and observation hooks through the scope
 * (MutatingScope::getCurrentTemplateArgumentFrame()), the way the current
 * ExpressionResultStorage is - not a service: it accumulates state per walk.
 *
 * Observing (pass 1): producers create UnresolvedTemplateArgumentType markers
 * for every template argument inferred from arguments and note the site here;
 * hooks record every send of a marked object to a declared type and every
 * lower bound a method call puts on it. finishObserving() resolves each
 * (site, template name) - see resolveObservation() for the rules - and the
 * second pass substitutes the resolutions through resolve(), which also
 * consults the parent frames (a closure body re-walked during its enclosing
 * body's second pass sees the enclosing resolutions).
 *
 * Holds only types, nodes and ints: never a scope or an expression result
 * (a scope references the container that would reference this frame - a cycle
 * the disabled cycle collector never frees).
 */
final class TemplateArgumentFrame
{

	/**
	 * Set on the synthetic nodes handlers build for an on-demand pricing (the
	 * parent constructor's `new`, a fabricated offsetGet()/__toString() call):
	 * a fresh node every walk, so it can never be a site the second pass finds
	 * again. Markers keyed by such a node are re-keyed by the handler that
	 * built it or stay unobserved.
	 */
	public const SYNTHETIC_SITE_ATTRIBUTE = 'templateArgumentSyntheticSite';

	/**
	 * Set on a node a handler builds in place of the real one (the nullsafe
	 * call's plain twin) to the real node, which is the site.
	 */
	public const ORIGINAL_SITE_ATTRIBUTE = 'templateArgumentOriginalSite';

	/**
	 * The return type of a call the analyser walks: the variant's return type
	 * with the inferred template arguments unresolved/resolved under the
	 * scope's frame, the legacy (generalizing) one otherwise.
	 */
	public static function returnTypeOfCall(ParametersAcceptor $acceptor, MutatingScope $scope, Expr $site, ?bool $allowUnresolved = null): Type
	{
		$frame = $scope->getCurrentTemplateArgumentFrame();
		if ($frame === null || !$acceptor instanceof ResolvedFunctionVariant) {
			return $acceptor->getReturnType();
		}
		$originalSite = $site->getAttribute(self::ORIGINAL_SITE_ATTRIBUTE);

		return $acceptor->getReturnTypeWithUnresolvedTemplateArguments(
			$originalSite instanceof Expr ? $originalSite : $site,
			$frame,
			$allowUnresolved ?? !$scope->nativeTypesPromoted,
		);
	}

	private bool $observing = true;

	/** @var array<int, array{Expr, int}> site object id => [site, statement index] */
	private array $sites = [];

	/** @var array<int, true> */
	private array $siteStatementIndexes = [];

	/**
	 * @var array<string, array{
	 *     marker: UnresolvedTemplateArgumentType,
	 *     initial: Type|null,
	 *     sends: list<array{Type, TemplateTypeVariance}>,
	 *     lowerBounds: list<Type>,
	 * }>
	 */
	private array $observations = [];

	/** @var array<string, Type> */
	private array $resolutions = [];

	private int $currentStatementIndex = 0;

	/**
	 * @param list<int> $statementStartTokenPositions start token position of each statement of the body, ascending
	 */
	public function __construct(
		private ?self $parent,
		private array $statementStartTokenPositions,
	)
	{
	}

	public function isObserving(): bool
	{
		return $this->observing;
	}

	public function setCurrentStatementIndex(int $index): void
	{
		$this->currentStatementIndex = $index;
	}

	/**
	 * A producer created this marker: attributes its site to the statement the
	 * site lives in (by token position - the first ask of a lazily typed result
	 * can happen while a later statement is current) and remembers the initial
	 * type so the site resolves even when nothing ever observes it.
	 */
	public function noteSite(UnresolvedTemplateArgumentType $marker): void
	{
		$site = $marker->getSite();
		if ($site->getAttribute(self::SYNTHETIC_SITE_ATTRIBUTE) === true) {
			return;
		}
		$siteId = spl_object_id($site);
		if (!array_key_exists($siteId, $this->sites)) {
			$statementIndex = $this->locateStatement($site->getStartTokenPos());
			$this->sites[$siteId] = [$site, $statementIndex];
			$this->siteStatementIndexes[$statementIndex] = true;
			if (TemplateArgumentStats::$enabled) {
				TemplateArgumentStats::increment('sitesCreated');
			}
		}

		$this->observation($marker);
	}

	public function hasSites(): bool
	{
		return count($this->sites) > 0;
	}

	public function firstSiteStatementIndex(): ?int
	{
		$first = null;
		foreach (array_keys($this->siteStatementIndexes) as $index) {
			if ($first !== null && $index >= $first) {
				continue;
			}

			$first = $index;
		}

		return $first;
	}

	public function ownsSiteInStatement(int $statementIndex): bool
	{
		return isset($this->siteStatementIndexes[$statementIndex]);
	}

	public function hasSiteAtOrAfter(int $statementIndex): bool
	{
		foreach (array_keys($this->siteStatementIndexes) as $index) {
			if ($index >= $statementIndex) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The marked object was sent to a declared type whose argument at the
	 * marker's position is $declaredArgument; $effectiveVariance is the call-site
	 * variance of that position, or the template's declared variance when the
	 * call site does not override it.
	 */
	public function recordSend(UnresolvedTemplateArgumentType $marker, Type $declaredArgument, TemplateTypeVariance $effectiveVariance): void
	{
		$this->observation($marker)['sends'][] = [$declaredArgument, $effectiveVariance];
	}

	/** A method call on the marked object passed $lowerBound where the template is expected. */
	public function recordLowerBound(UnresolvedTemplateArgumentType $marker, Type $lowerBound): void
	{
		$this->observation($marker)['lowerBounds'][] = $lowerBound;
	}

	/**
	 * @return array{
	 *     marker: UnresolvedTemplateArgumentType,
	 *     initial: Type|null,
	 *     sends: list<array{Type, TemplateTypeVariance}>,
	 *     lowerBounds: list<Type>,
	 * }
	 */
	private function &observation(UnresolvedTemplateArgumentType $marker): array
	{
		$key = self::key($marker->getSite(), $marker->getTemplateName());
		if (!array_key_exists($key, $this->observations)) {
			$this->observations[$key] = [
				'marker' => $marker,
				'initial' => null,
				'sends' => [],
				'lowerBounds' => [],
			];
		}

		$initial = $marker->getInitialType();
		if ($initial !== null) {
			// a loop may re-produce the marker with a widened initial type: the
			// initial to clamp against is everything the site was ever seen with
			$this->observations[$key]['initial'] = $this->observations[$key]['initial'] === null
				? $initial
				: TypeCombinator::union($this->observations[$key]['initial'], $initial);
		}

		return $this->observations[$key];
	}

	/** Ends the observation pass: every observed (site, template name) gets its resolution. */
	public function finishObserving(): void
	{
		$this->observing = false;
		foreach (array_keys($this->observations) as $key) {
			$this->resolveKey($key);
		}
	}

	/** @var array<string, true> */
	private array $resolving = [];

	private function resolveKey(string $key): Type
	{
		if (array_key_exists($key, $this->resolutions)) {
			return $this->resolutions[$key];
		}
		$observation = $this->observations[$key];
		if (isset($this->resolving[$key])) {
			// a site whose inferred argument refers back to itself through another
			// site (wrap($x = new Foo($x))): the inferred type stands
			return $observation['marker']->getDelegate();
		}

		$this->resolving[$key] = true;
		try {
			return $this->resolutions[$key] = $this->resolveObservation($observation);
		} finally {
			unset($this->resolving[$key]);
		}
	}

	/**
	 * Replaces the markers of sites this frame observed inside a type by their
	 * resolutions - a resolution never contains a marker, and a send must be
	 * checked against what the inferred argument resolves to, not against the
	 * opaque marker (wrap(new Foo(1)) sent to Bar<Foo<int>> resolves the outer
	 * site to Foo<int> only once the inner one is int).
	 */
	private function substituteResolutions(Type $type): Type
	{
		if ($type instanceof UnresolvedTemplateArgumentType) {
			return $this->substituteMarker($type);
		}

		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if ($type instanceof UnresolvedTemplateArgumentType) {
				return $this->substituteMarker($type);
			}

			return $traverse($type);
		});
	}

	private function substituteMarker(UnresolvedTemplateArgumentType $marker): Type
	{
		$key = self::key($marker->getSite(), $marker->getTemplateName());
		if (array_key_exists($key, $this->observations)) {
			return $this->resolveKey($key);
		}

		$resolved = $this->parent !== null ? $this->parent->resolve($marker->getSite(), $marker->getTemplateName()) : null;

		return $resolved ?? $this->substituteResolutions($marker->getDelegate());
	}

	/**
	 * @param array{
	 *     marker: UnresolvedTemplateArgumentType,
	 *     initial: Type|null,
	 *     sends: list<array{Type, TemplateTypeVariance}>,
	 *     lowerBounds: list<Type>,
	 * } $observation
	 */
	private function resolveObservation(array $observation): Type
	{
		$initial = $observation['initial'] !== null ? $this->substituteResolutions($observation['initial']) : null;
		$lowerBounds = [];
		foreach ($observation['lowerBounds'] as $lowerBound) {
			$lowerBounds[] = $this->substituteResolutions($lowerBound);
		}
		$templateVariance = $observation['marker']->getTemplate()->getVariance();

		// nothing inferred, or never (an empty array): every send accepts it
		$acceptsAnything = $initial === null || $initial instanceof NeverType;
		// a covariant template already accepts every subtype - a known initial
		// type is never clamped
		if (!$templateVariance->covariant() || $acceptsAnything) {
			$covariantFallback = null;
			foreach ($observation['sends'] as [$sent, $variance]) {
				if ($variance->contravariant()) {
					// Foo<contravariant int> accepts Foo<X> for every X wider than int
					$lowerBounds[] = $sent;
					continue;
				}
				if ($variance->covariant()) {
					// an upper bound; with nothing inferred it is the best information there is
					if ($acceptsAnything) {
						$covariantFallback ??= $sent;
					}
					continue;
				}
				if (!$variance->invariant()) {
					continue;
				}
				// invariant: the first send that accepts what was inferred resolves the
				// argument; a later incompatible send is reported by the second pass
				if (!$acceptsAnything && !$sent->isSuperTypeOf($initial)->yes()) {
					continue;
				}

				if (TemplateArgumentStats::$enabled) {
					TemplateArgumentStats::increment('resolvedBySend');
				}
				return $sent;
			}

			if ($covariantFallback !== null) {
				if (TemplateArgumentStats::$enabled) {
					TemplateArgumentStats::increment('resolvedBySend');
				}
				return $covariantFallback;
			}
		}

		$parts = $lowerBounds;
		if ($initial !== null) {
			$parts[] = $initial;
		}
		if (count($parts) === 0) {
			if (TemplateArgumentStats::$enabled) {
				TemplateArgumentStats::increment('resolvedUnconstrained');
			}
			return $this->resolveUnconstrained($observation['marker']);
		}

		if (TemplateArgumentStats::$enabled) {
			TemplateArgumentStats::increment(count($lowerBounds) > 0 ? 'resolvedWithLowerBounds' : 'resolvedToInitial');
		}
		return TypeCombinator::union(...$parts);
	}

	/**
	 * The resolution of the site's template argument, or - for a site this
	 * frame never observed (never asked during the observation pass, a native
	 * flavour) - what an unconstrained argument resolves to.
	 */
	public function resolveOrUnconstrained(Expr $site, TemplateType $template): Type
	{
		return $this->resolve($site, $template->getName()) ?? $this->resolveUnconstrained(new UnresolvedTemplateArgumentType($site, $template, null));
	}

	/**
	 * Nothing was inferred, sent or passed in: the template's default, else
	 * its bound when it says something (`T of Foo`, `U of T` - resolved
	 * against the sibling arguments), else never - the object holds nothing.
	 */
	private function resolveUnconstrained(UnresolvedTemplateArgumentType $marker): Type
	{
		$template = $marker->getTemplate();
		$default = $template->getDefault();
		if ($default !== null) {
			return $default;
		}

		$bound = $template->getBound();
		if ($bound instanceof MixedType && !$bound instanceof TemplateType) {
			return new NeverType();
		}
		if (!$bound->hasTemplateOrLateResolvableType()) {
			return $bound;
		}

		$site = $marker->getSite();
		$scope = $template->getScope();

		return TypeTraverser::map($bound, function (Type $type, callable $traverse) use ($site, $scope): Type {
			if ($type instanceof TemplateType && $type->getScope()->equals($scope)) {
				return $this->resolve($site, $type->getName()) ?? $type->getDefault() ?? $traverse($type->getBound());
			}

			return $traverse($type);
		});
	}

	/**
	 * The resolved type of a template argument of the site, or null for a site
	 * this frame and its parents never observed.
	 */
	public function resolve(Expr $site, string $templateName): ?Type
	{
		$key = self::key($site, $templateName);
		if (array_key_exists($key, $this->resolutions)) {
			return $this->resolutions[$key];
		}

		if ($this->parent !== null) {
			return $this->parent->resolve($site, $templateName);
		}

		return null;
	}

	/**
	 * Distinguishes cache entries computed while observing from those computed
	 * with the resolutions installed (the closure type cache keys on scope state
	 * that does not change between the two passes).
	 */
	public function getResolutionCacheKeySuffix(): string
	{
		$frame = $this;
		while ($frame !== null) {
			if (!$frame->observing) {
				return sprintf('|templateArguments:%d', spl_object_id($frame));
			}

			$frame = $frame->parent;
		}

		return '';
	}

	public static function isUninformativeSendTarget(Type $declaredArgument): bool
	{
		// Foo<mixed> accepts every Foo<X> (TemplateTypeVariance::isValidVariance)
		// and a declared argument with unresolved template types is no target yet
		return ($declaredArgument instanceof MixedType && !$declaredArgument instanceof TemplateType)
			|| $declaredArgument->hasTemplateOrLateResolvableType();
	}

	private static function key(Expr $site, string $templateName): string
	{
		return spl_object_id($site) . '#' . $templateName;
	}

	private function locateStatement(int $tokenPosition): int
	{
		if ($tokenPosition < 0 || count($this->statementStartTokenPositions) === 0) {
			return $this->currentStatementIndex;
		}

		$low = 0;
		$high = count($this->statementStartTokenPositions) - 1;
		if ($tokenPosition < $this->statementStartTokenPositions[0]) {
			return $this->currentStatementIndex;
		}
		while ($low < $high) {
			$mid = ($low + $high + 1) >> 1;
			if ($this->statementStartTokenPositions[$mid] <= $tokenPosition) {
				$low = $mid;
			} else {
				$high = $mid - 1;
			}
		}

		return $low;
	}

}
