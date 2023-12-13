<?php declare(strict_types = 1);

namespace Bug5008;

class SomeHelper
{
	/**
	 * @return -1|0|1
	 */
	public static function f(int $a, int $b): int
	{
		return $a <=> $b;
	}
}
