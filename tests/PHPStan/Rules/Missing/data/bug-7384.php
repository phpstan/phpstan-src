<?php declare(strict_types = 1);

namespace Bug7384;

class Cl
{
    /**
     * @return void
     */
    public static function assertTrueSimple(bool $v): void
    {
		if (!$v) {
			throw new \Error('Value is not true');
		}
	}

    /**
     * @return ($v is true ? void : never)
     */
    public static function assertTrue(bool $v): void
    {
		if (!$v) {
			throw new \Error('Value is not true');
		}
	}
}
