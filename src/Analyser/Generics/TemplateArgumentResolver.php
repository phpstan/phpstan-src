<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node;
use PhpParser\Node\Expr\Closure;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_diff_key;
use function count;
use function spl_object_id;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/TemplateArgumentResolver.cpp')]
final class TemplateArgumentResolver
{

	/**
	 * A template argument that resolved to something else than it stood for
	 * while observing changes the values read out of its object - the closures
	 * those values were passed to observed stale lower bounds (the pass-1
	 * `1|2` of `$c($collection->first())` when the collection resolves to
	 * `Collection<int>`). Their signatures are then observed again, walking with
	 * the template arguments resolved - the returned frame is observing closures,
	 * see resolveObservedClosures().
	 *
	 * @param list<int> $statementStartTokenPositions
	 * @param Node\Stmt[] $closureSignatureStmts
	 */
	public function resolve(TemplateArgumentConstraints $constraints, ?TemplateArgumentFrame $parent, array $statementStartTokenPositions, ?Node $closureSignatureBody = null, array $closureSignatureStmts = []): TemplateArgumentFrame
	{
		$closureSiteIds = [];
		[$observations, $siteIndexes] = $this->collectObservations($constraints, $statementStartTokenPositions, false, $closureSiteIds);
		$resolutions = (new TemplateArgumentSolver($observations, $parent))->solve();
		$settledClosureSites = $this->settleClosureSites($observations, $resolutions);

		if (count($settledClosureSites) < count($closureSiteIds) && $this->hasChangedTemplateArgument($observations, $resolutions)) {
			$templateSiteStatementIndexes = [];
			foreach ($siteIndexes as $id => $index) {
				if (isset($closureSiteIds[$id])) {
					continue;
				}
				$templateSiteStatementIndexes[$index] = true;
			}
			$closureKeys = [];
			foreach ($observations as $key => $observation) {
				if (!ClosureSignatureInference::isClosureSignatureMarker($observation['marker'])) {
					continue;
				}
				$closureKeys[$key] = true;
			}
			if (TemplateArgumentStats::$enabled) {
				TemplateArgumentStats::increment('closureObservationPasses');
			}

			return new TemplateArgumentFrame($parent, array_diff_key($resolutions, $closureKeys), $templateSiteStatementIndexes, $closureSignatureBody, $closureSignatureStmts, observingClosures: true);
		}

		if (TemplateArgumentStats::$enabled && count($settledClosureSites) > 0) {
			TemplateArgumentStats::increment('closureSitesSettled', count($settledClosureSites));
		}

		return new TemplateArgumentFrame($parent, $resolutions, $this->collectSiteStatementIndexes($siteIndexes, $settledClosureSites), $closureSignatureBody, $closureSignatureStmts, $settledClosureSites, byRefSites: $this->collectByRefSites($observations, $siteIndexes));
	}

	/**
	 * Resolves the closure signatures the closure observation pass observed
	 * under the frame resolve() returned for it.
	 *
	 * @param list<int> $statementStartTokenPositions
	 */
	public function resolveObservedClosures(TemplateArgumentConstraints $constraints, TemplateArgumentFrame $frame, array $statementStartTokenPositions): TemplateArgumentFrame
	{
		$closureSiteIds = [];
		[$observations, $siteIndexes] = $this->collectObservations($constraints, $statementStartTokenPositions, true, $closureSiteIds);
		$resolutions = (new TemplateArgumentSolver($observations, $frame))->solve();
		$settledClosureSites = $this->settleClosureSites($observations, $resolutions);
		if (TemplateArgumentStats::$enabled && count($settledClosureSites) > 0) {
			TemplateArgumentStats::increment('closureSitesSettled', count($settledClosureSites));
		}

		return $frame->withObservedClosures($resolutions, $this->collectSiteStatementIndexes($siteIndexes, $settledClosureSites), $settledClosureSites, $this->collectByRefSites($observations, $siteIndexes));
	}

	/**
	 * @param list<int> $statementStartTokenPositions
	 * @param array<int, true> $closureSiteIds
	 * @param-out array<int, true> $closureSiteIds
	 * @return array{array<string, array{marker: UnresolvedTemplateArgumentType, initial: Type|null, sends: list<array{Type, TemplateTypeVariance}>, lowerBounds: list<Type>, unconstrainingSend: bool}>, array<int, int>}
	 */
	private function collectObservations(TemplateArgumentConstraints $constraints, array $statementStartTokenPositions, bool $closuresOnly, array &$closureSiteIds): array
	{
		$observations = [];
		$sites = [];
		$siteIndexes = [];
		foreach ($constraints->getFacts() as [$marker, $type, $variance, $unconstraining]) {
			$isClosureMarker = ClosureSignatureInference::isClosureSignatureMarker($marker);
			if ($closuresOnly && !$isClosureMarker) {
				continue;
			}
			$key = spl_object_id($marker->getSite()) . '#' . $marker->getTemplateName();
			if ($unconstraining && !isset($observations[$key])) {
				continue;
			}
			$observation = $observations[$key] ?? [
				'marker' => $marker,
				'initial' => null,
				'sends' => [],
				'lowerBounds' => [],
				'unconstrainingSend' => false,
			];
			$initial = $marker->getInitialType();
			if ($initial !== null) {
				$observation['initial'] = $observation['initial'] === null ? $initial : TypeCombinator::union($observation['initial'], $initial);
			}
			if ($unconstraining) {
				$observation['unconstrainingSend'] = true;
			} elseif ($type !== null && $variance !== null) {
				$observation['sends'][] = [$type, $variance];
			} elseif ($type !== null) {
				$observation['lowerBounds'][] = $type;
			}
			$observations[$key] = $observation;
			if ($type !== null || $unconstraining) {
				continue;
			}
			$site = $marker->getSite();
			$id = spl_object_id($site);
			if (isset($sites[$id])) {
				continue;
			}
			$sites[$id] = $site;
			$siteIndexes[$id] = $this->locateStatement($site->getStartTokenPos(), $statementStartTokenPositions);
			if ($isClosureMarker) {
				$closureSiteIds[$id] = true;
			}
			if (!TemplateArgumentStats::$enabled || $closuresOnly) {
				continue;
			}

			TemplateArgumentStats::increment($isClosureMarker ? 'closureSitesCreated' : 'sitesCreated');
		}

		return [$observations, $siteIndexes];
	}

	/**
	 * A closure site whose every marker resolved to what it stood for in the
	 * observation pass is settled: the second pass keeps its markers, so the
	 * recorded walk of its statement - and of every statement it reaches -
	 * stands and is replayed.
	 *
	 * @param array<string, array{marker: UnresolvedTemplateArgumentType, initial: Type|null, sends: list<array{Type, TemplateTypeVariance}>, lowerBounds: list<Type>, unconstrainingSend: bool}> $observations
	 * @param array<string, Type> $resolutions
	 * @return array<int, true>
	 */
	private function settleClosureSites(array $observations, array $resolutions): array
	{
		$settledClosureSites = [];
		foreach ($observations as $key => $observation) {
			$marker = $observation['marker'];
			if (!ClosureSignatureInference::isClosureSignatureMarker($marker)) {
				continue;
			}
			$id = spl_object_id($marker->getSite());
			$settled = $settledClosureSites[$id] ?? true;
			if (!$settled) {
				continue;
			}
			$resolution = $resolutions[$key] ?? null;
			if (ClosureSignatureInference::isByRefMarker($marker)) {
				// every invocation seen: the second pass applies the effects where
				// it runs; escaped: the creation-time fixpoint, seeded with the
				// states it was invoked from
				$settled = $observation['unconstrainingSend'] && ($resolution === null || $resolution->equals($marker->getDelegate()));
			} elseif (ClosureSignatureInference::isReturnMarker($marker)) {
				$settled = $resolution === null || $resolution instanceof MixedType;
			} else {
				$settled = $resolution === null || $resolution->equals($marker->getDelegate());
			}
			$settledClosureSites[$id] = $settled;
		}

		$settled = [];
		foreach ($settledClosureSites as $id => $isSettled) {
			if (!$isSettled) {
				continue;
			}
			$settled[$id] = true;
		}

		return $settled;
	}

	/**
	 * @param array<string, array{marker: UnresolvedTemplateArgumentType, initial: Type|null, sends: list<array{Type, TemplateTypeVariance}>, lowerBounds: list<Type>, unconstrainingSend: bool}> $observations
	 * @param array<int, int> $siteIndexes
	 * @return array<int, array{Closure, int, bool}>
	 */
	private function collectByRefSites(array $observations, array $siteIndexes): array
	{
		$byRefSites = [];
		foreach ($observations as $observation) {
			$marker = $observation['marker'];
			if (!ClosureSignatureInference::isByRefMarker($marker)) {
				continue;
			}
			$site = $marker->getSite();
			if (!$site instanceof Closure) {
				continue;
			}
			$id = spl_object_id($site);
			if (!isset($siteIndexes[$id])) {
				continue;
			}
			$local = !$observation['unconstrainingSend'] && ($byRefSites[$id][2] ?? true);
			$byRefSites[$id] = [$site, $siteIndexes[$id], $local];
		}

		return $byRefSites;
	}

	/**
	 * @param array<string, array{marker: UnresolvedTemplateArgumentType, initial: Type|null, sends: list<array{Type, TemplateTypeVariance}>, lowerBounds: list<Type>, unconstrainingSend: bool}> $observations
	 * @param array<string, Type> $resolutions
	 */
	private function hasChangedTemplateArgument(array $observations, array $resolutions): bool
	{
		foreach ($observations as $key => $observation) {
			$marker = $observation['marker'];
			if (ClosureSignatureInference::isClosureSignatureMarker($marker)) {
				continue;
			}
			$resolution = $resolutions[$key] ?? null;
			if ($resolution !== null && !$resolution->equals($observation['initial'] ?? $marker->getDelegate())) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<int, int> $siteIndexes
	 * @param array<int, true> $settledClosureSites
	 * @return array<int, true>
	 */
	private function collectSiteStatementIndexes(array $siteIndexes, array $settledClosureSites): array
	{
		$siteStatementIndexes = [];
		foreach ($siteIndexes as $id => $index) {
			if (isset($settledClosureSites[$id])) {
				continue;
			}
			$siteStatementIndexes[$index] = true;
		}

		return $siteStatementIndexes;
	}

	/** @param list<int> $positions */
	private function locateStatement(int $tokenPosition, array $positions): int
	{
		$low = 0;
		$high = count($positions) - 1;
		while ($low < $high) {
			$mid = ($low + $high + 1) >> 1;
			if ($positions[$mid] <= $tokenPosition) {
				$low = $mid;
			} else {
				$high = $mid - 1;
			}
		}

		return $low;
	}

}
