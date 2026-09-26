<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use function array_filter;
use function count;
use function spl_object_id;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/TemplateArgumentResolver.cpp')]
final class TemplateArgumentResolver
{

	/** @param list<int> $statementStartTokenPositions */
	public function resolve(TemplateArgumentConstraints $constraints, ?TemplateArgumentFrame $parent, array $statementStartTokenPositions): TemplateArgumentFrame
	{
		$observations = [];
		$sites = [];
		$siteStatementIndexes = [];
		$siteIndexes = [];
		foreach ($constraints->getFacts() as [$marker, $type, $variance, $unconstraining]) {
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
			if (!TemplateArgumentStats::$enabled) {
				continue;
			}

			TemplateArgumentStats::increment(ClosureSignatureInference::isClosureSignatureMarker($marker) ? 'closureSitesCreated' : 'sitesCreated');
		}

		$resolutions = (new TemplateArgumentSolver($observations, $parent))->solve();

		// a closure site whose every marker resolved to what it stood for in the
		// observation pass is settled: the second pass keeps its markers, so the
		// recorded walk of its statement - and of every statement it reaches -
		// stands and is replayed
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
			if (ClosureSignatureInference::isReturnMarker($marker)) {
				$settled = $resolution === null || $resolution instanceof MixedType;
			} else {
				$settled = $resolution === null || $resolution->equals($marker->getDelegate());
			}
			$settledClosureSites[$id] = $settled;
		}
		$settledClosureSites = array_filter($settledClosureSites);

		foreach ($siteIndexes as $id => $index) {
			if (isset($settledClosureSites[$id])) {
				continue;
			}
			$siteStatementIndexes[$index] = true;
		}
		if (TemplateArgumentStats::$enabled && count($settledClosureSites) > 0) {
			TemplateArgumentStats::increment('closureSitesSettled', count($settledClosureSites));
		}

		return new TemplateArgumentFrame($parent, $resolutions, $siteStatementIndexes, settledClosureSites: $settledClosureSites);
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
