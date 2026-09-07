<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node\Expr;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function spl_object_id;

/** A single solve's memoization; never retained by a scope or a type callback. */
final class TemplateArgumentSolver
{

	/** @var array<string, Type> */
	private array $resolutions = [];

	/** @param array<string, array{marker: UnresolvedTemplateArgumentType, initial: Type|null, sends: list<array{Type, TemplateTypeVariance}>, lowerBounds: list<Type>, unconstrainingSend: bool}> $observations */
	public function __construct(
		private array $observations,
		private ?TemplateArgumentFrame $parent,
	)
	{
	}

	/** @return array<string, Type> */
	public function solve(): array
	{
		$this->mergeEqualArguments();
		foreach (array_keys($this->observations) as $key) {
			$this->resolveKey($key);
		}
		foreach ($this->representatives as $key => $representative) {
			$this->resolutions[$key] = $this->resolveKey($representative);
		}

		return $this->resolutions;
	}

	/** @var array<string, string> */
	private array $representatives = [];

	private function representative(string $key): string
	{
		$representative = $this->representatives[$key] ?? $key;
		if ($representative === $key) {
			return $key;
		}
		return $this->representatives[$key] = $this->representative($representative);
	}

	/** Invariant arguments share one variable until all their bounds are known. */
	private function mergeEqualArguments(): void
	{
		$ranks = [];
		foreach ($this->observations as $key => $observation) {
			foreach ($observation['sends'] as [$sent, $variance]) {
				if (!$variance->invariant() || !$sent instanceof UnresolvedTemplateArgumentType) {
					continue;
				}
				$other = self::key($sent->getSite(), $sent->getTemplateName());
				if (!isset($this->observations[$other])) {
					continue;
				}
				$left = $this->representative($key);
				$right = $this->representative($other);
				if ($left === $right) {
					continue;
				}
				$leftRank = $ranks[$left] ?? 0;
				$rightRank = $ranks[$right] ?? 0;
				if ($leftRank < $rightRank) {
					$this->representatives[$left] = $right;
				} else {
					$this->representatives[$right] = $left;
					if ($leftRank === $rightRank) {
						$ranks[$left] = $leftRank + 1;
					}
				}
			}
		}
		if ($this->representatives === []) {
			return;
		}
		$observations = [];
		foreach ($this->observations as $key => $observation) {
			$representative = $this->representative($key);
			$this->representatives[$key] = $representative;
			$initial = $observation['initial'];
			$observation['initial'] = $initial !== null ? $this->removeSelfBounds($initial, $representative) : null;
			$observation['sends'] = array_values(array_filter($observation['sends'], fn (array $send): bool => !$send[0] instanceof UnresolvedTemplateArgumentType
					|| $this->representative(self::key($send[0]->getSite(), $send[0]->getTemplateName())) !== $representative));
			if (!isset($observations[$representative])) {
				$observations[$representative] = $observation;
				continue;
			}
			$merged = $observations[$representative];
			if ($observation['initial'] !== null) {
				$merged['initial'] = $merged['initial'] !== null ? TypeCombinator::union($merged['initial'], $observation['initial']) : $observation['initial'];
			}
			$merged['sends'] = array_merge($merged['sends'], $observation['sends']);
			$merged['lowerBounds'] = array_merge($merged['lowerBounds'], $observation['lowerBounds']);
			$merged['unconstrainingSend'] = $merged['unconstrainingSend'] || $observation['unconstrainingSend'];
			$observations[$representative] = $merged;
		}
		$this->observations = $observations;
	}

	private function removeSelfBounds(Type $type, string $key): Type
	{
		if ($type instanceof UnresolvedTemplateArgumentType && $this->representative(self::key($type->getSite(), $type->getTemplateName())) === $key) {
			return new NeverType();
		}
		if ($type instanceof UnionType) {
			return $type->traverse(fn (Type $member): Type => $this->removeSelfBounds($member, $key));
		}
		return $type;
	}

	/** @var array<string, true> */
	private array $resolving = [];

	private function resolveKey(string $key): Type
	{
		$key = $this->representative($key);
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
	 * Replaces the markers of observed sites inside a type by their
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
		$key = $this->representative(self::key($marker->getSite(), $marker->getTemplateName()));
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
	 *     unconstrainingSend: bool,
	 * } $observation
	 */
	private function resolveObservation(array $observation): Type
	{
		$initial = $observation['initial'] !== null ? $this->substituteResolutions($observation['initial']) : null;
		$template = $observation['marker']->getTemplate();
		$lowerBounds = [];
		foreach ($observation['lowerBounds'] as $lowerBound) {
			$inferred = $template->inferTemplateTypes($this->substituteResolutions($lowerBound))->getType($template->getName());
			if ($inferred === null) {
				continue;
			}
			$lowerBounds[] = $inferred;
		}
		$templateVariance = $template->getVariance();

		// nothing inferred, or never (an empty array): every send accepts it
		$acceptsAnything = $initial === null || $initial instanceof NeverType;
		// a covariant template already accepts every subtype - a known initial
		// type is never clamped
		if (!$templateVariance->covariant() || $acceptsAnything) {
			$covariantFallback = null;
			foreach ($observation['sends'] as [$sent, $variance]) {
				$sent = $this->substituteResolutions($sent);
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
		// a never initial adds nothing to a union and would otherwise hide the
		// "nothing was inferred" case below
		if ($initial !== null && !$initial instanceof NeverType) {
			$parts[] = $initial;
		}
		if (count($parts) === 0) {
			if ($observation['unconstrainingSend']) {
				// sent to a target that accepts anything: the object is in use, so
				// the template's default or bound describes the argument - never
				// would make every later read of it an error
				return $template->getDefault() ?? $template->getBound();
			}
			if ($initial instanceof NeverType) {
				return $initial;
			}
			if (TemplateArgumentStats::$enabled) {
				TemplateArgumentStats::increment('resolvedUnconstrained');
			}

			return TemplateArgumentFrame::resolveUnconstrained($observation['marker']->getSite(), $observation['marker']->getTemplate(), fn (Expr $site, string $templateName): ?Type => $this->resolve($site, $templateName));
		}

		if (TemplateArgumentStats::$enabled) {
			TemplateArgumentStats::increment(count($lowerBounds) > 0 ? 'resolvedWithLowerBounds' : 'resolvedToInitial');
		}
		return TypeCombinator::union(...$parts);
	}

	/**
	 * The resolved type of a template argument of the site, or null for a site
	 * this solve and its parent context never observed.
	 */
	private function resolve(Expr $site, string $templateName): ?Type
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

	private static function key(Expr $site, string $templateName): string
	{
		return spl_object_id($site) . '#' . $templateName;
	}

}
