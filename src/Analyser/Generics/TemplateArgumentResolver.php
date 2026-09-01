<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\TypeCombinator;
use function count;
use function spl_object_id;

#[AutowiredService]
final class TemplateArgumentResolver
{

	/** @param list<int> $statementStartTokenPositions */
	public function resolve(TemplateArgumentConstraints $constraints, ?TemplateArgumentFrame $parent, array $statementStartTokenPositions): TemplateArgumentFrame
	{
		$observations = [];
		$sites = [];
		$siteStatementIndexes = [];
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
			$siteStatementIndexes[$this->locateStatement($site->getStartTokenPos(), $statementStartTokenPositions)] = true;
			if (!TemplateArgumentStats::$enabled) {
				continue;
			}

			TemplateArgumentStats::increment('sitesCreated');
		}

		return new TemplateArgumentFrame($parent, (new TemplateArgumentSolver($observations, $parent))->solve(), $siteStatementIndexes);
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
