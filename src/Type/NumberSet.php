<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * The numbers a type holds, as Type::toNumberSet() describes them, for interval
 * arithmetic that needs no knowledge of the Type classes.
 *
 * Integer intervals are closed, with null for an unbounded side. Float intervals
 * are closed in PHP's ordering of the doubles, where ±INF are values and both
 * zeros are one point (see FloatRangeType). NAN is in no interval. Each interval
 * stands for one member of the type, so a constant is an interval of one point
 * and intervals of different members can touch.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/NumberSet.cpp')]
final class NumberSet
{

	/**
	 * @param list<array{int|null, int|null}> $integerIntervals
	 * @param list<array{float, float}> $floatIntervals
	 */
	public function __construct(
		private array $integerIntervals,
		private array $floatIntervals,
		private bool $nan,
	)
	{
	}

	/**
	 * @return list<array{int|null, int|null}>
	 */
	public function getIntegerIntervals(): array
	{
		return $this->integerIntervals;
	}

	/**
	 * @return list<array{float, float}>
	 */
	public function getFloatIntervals(): array
	{
		return $this->floatIntervals;
	}

	public function hasNan(): bool
	{
		return $this->nan;
	}

}
