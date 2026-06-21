<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

final class BleedingEdgeToggle
{

	private static bool $bleedingEdge = false;

	public static function isBleedingEdge(): bool
	{
		return self::$bleedingEdge;
	}

	public static function setBleedingEdge(bool $bleedingEdge): void
	{
		self::$bleedingEdge = $bleedingEdge;
	}

	/**
	 * Runs the callback with the toggle set to $bleedingEdge and restores the previous
	 * value before returning, so the global toggle is never observable as mutated outside
	 * this call. When used from a data provider, the data sets must be produced by the
	 * callback so that the contained objects are constructed while the toggle is set -
	 * holding the toggle across a `yield` would otherwise leak it into unrelated tests.
	 *
	 * @template T
	 * @param callable(): T $callback
	 * @return T
	 */
	public static function withBleedingEdge(bool $bleedingEdge, callable $callback)
	{
		$backup = self::$bleedingEdge;
		self::$bleedingEdge = $bleedingEdge;
		try {
			return $callback();
		} finally {
			self::$bleedingEdge = $backup;
		}
	}

}
