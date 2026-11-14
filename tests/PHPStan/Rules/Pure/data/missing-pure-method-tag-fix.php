<?php

namespace MissingPureMethodTagFix;

final class Foo
{

	/**
	 * @param positive-int $a
	 */
	public function double(int $a): int
	{
		return $a * 2;
	}

	private function one(): int
	{
		return 1;
	}

	/** Returns two. */
	private function two(): int
	{
		return 2;
	}

}
