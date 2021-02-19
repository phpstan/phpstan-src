<?php

namespace Bug3117;

interface Temporal
{

	/**
	 * @return static
	 */
	public function adjustedWith(TemporalAdjuster $adjuster): Temporal;

}

interface TemporalAdjuster
{

	/**
	 * @template T of Temporal
	 *
	 * @param T $temporal
	 *
	 * @return T
	 */
	public function adjustInto(Temporal $temporal) : Temporal;

}

final class SimpleTemporal implements Temporal, TemporalAdjuster
{

	public function adjustInto(Temporal $temporal): Temporal
	{
		if ($temporal instanceof self) {
			return $this;
		}

		return $temporal->adjustedWith($this);
	}

	/**
	 * @return static
	 */
	public function adjustedWith(TemporalAdjuster $adjuster): Temporal
	{
		return $this;
	}

}
