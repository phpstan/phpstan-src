<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug7184;

class Character
{

	private const MAX_CHARACTER = 4;  // ISSUE, this is being used on line 10.

	/** @param int<1,self::MAX_CHARACTER> $int */
	public function __construct(
		public int $int,
	) {}
}
