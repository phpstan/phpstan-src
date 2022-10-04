<?php declare(strict_types = 1);

namespace Bug8076;

class HelloWorld
{
	/**
	 * @param array{maybe?: string} $in
	 */
	public static function doThing(array $in): void
	{
		if(is_string($in['maybe'] ?? null)) {
		} else {
		}
	}
}
