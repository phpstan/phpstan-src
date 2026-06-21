<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use Override;
use PHPStan\DependencyInjection\BleedingEdgeToggle;

/**
 * Base test case for tests that depend on the global static BleedingEdgeToggle.
 *
 * It centralizes the set/restore machinery so that neither a test method nor its
 * data provider ever leaves the global toggle mutated. Data providers are evaluated
 * lazily and may be abandoned mid-iteration (filtering, random ordering, early stop);
 * holding the toggle across a `yield` would otherwise leak it into unrelated tests and
 * make every ConstantArrayType constructed afterwards sealed, intermittently flipping
 * unrelated results.
 *
 * @api
 */
abstract class PHPStanBleedingEdgeToggleTestCase extends PHPStanTestCase
{

	#[Override]
	protected function tearDown(): void
	{
		// Safety net: restore the global default even if a test method or a partially
		// consumed data provider left the toggle mutated.
		BleedingEdgeToggle::setBleedingEdge(false);

		parent::tearDown();
	}

	/**
	 * Runs the callback with the BleedingEdgeToggle set to $bleedingEdge and restores
	 * the previous value before returning, so the global toggle is never observable as
	 * mutated outside this call. When used from a data provider, the data sets must be
	 * produced by the callback so that the contained objects are constructed while the
	 * toggle is set.
	 *
	 * @template T
	 * @param callable(): T $callback
	 * @return T
	 */
	protected static function withBleedingEdge(bool $bleedingEdge, callable $callback)
	{
		$backup = BleedingEdgeToggle::isBleedingEdge();
		BleedingEdgeToggle::setBleedingEdge($bleedingEdge);
		try {
			return $callback();
		} finally {
			BleedingEdgeToggle::setBleedingEdge($backup);
		}
	}

}
