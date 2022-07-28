<?php declare(strict_types=1);

namespace Bug1016b;

class HelloWorld
{
	const A_A = 'a';
	const A_B = 'b';

	/**
	 * @param HelloWorld::A_* $a
	 */
	public function sayA(string $a): int
	{
		switch ($a) {
			case self::A_A:
				$z = 1;
				break;
			case self::A_B:
				$z = 2;
				break;
		}
		return $z;
	}
}
