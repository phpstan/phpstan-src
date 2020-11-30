<?php declare(strict_types = 1);

namespace Bug577;

class Test
{
	public static function step1(?int $foo): void
	{
		if ($foo > 1) {
			static::step2($foo);
		}
	}

	public static function step2(int $bar): void
	{
		var_dump($bar);
	}
}
